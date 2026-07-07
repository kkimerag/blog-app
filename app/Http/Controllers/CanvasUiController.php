<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlogSite;
use Canvas\Models\User;
use Canvas\Events\PostViewed;
use Canvas\Models\Post;
use Canvas\Models\Tag;
use Canvas\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CanvasUiController extends Controller
{
    private function currentSite(Request $request): BlogSite
    {
        $site = $request->attributes->get('blog_site');

        abort_unless($site instanceof BlogSite, 404);

        return $site;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $site = $this->currentSite($request);

        return view('canvas-ui')->with([
            'config' => [
                'canvasPath' => config('canvas.path'),
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'domain' => $site->domain,
                    'path' => $site->path,
                    'settings' => $site->settings,
                ],
                'user' => $request->user('canvas'),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPosts(Request $request): LengthAwarePaginator
    {
        return Post::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->latest()
            ->published()
            ->with('user', 'topic')
            ->paginate();
    }

    public function sitemap(Request $request)
    {
        $site = $this->currentSite($request);
        $posts = Post::query()
            ->where('site_id', $site->id)
            ->published()
            ->latest('updated_at')
            ->get(['slug', 'updated_at']);

        $urls = collect([
            [
                'loc' => url('/'),
                'lastmod' => now()->toAtomString(),
            ],
        ])->merge($posts->map(function (Post $post) {
            return [
                'loc' => url("/posts/{$post->slug}"),
                'lastmod' => optional($post->updated_at)->toAtomString(),
            ];
        }));

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPost(Request $request, $slug): JsonResponse
    {
        $post = Post::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->published()
            ->with('user', 'tags', 'topic')
            ->firstWhere('slug', $slug);

        if ($post) {
            $meta = $post->meta ?? [];
            $meta['canonical_link'] ??= url("/posts/{$post->slug}");
            $post->meta = $meta;

            event(new PostViewed($post));

            return response()->json($post, 200);
        } else {
            return response()->json(null, 404);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getTags(Request $request): string
    {
        return Tag::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->get()
            ->toJson();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTag(Request $request, $slug): JsonResponse
    {
        $tag = Tag::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->firstWhere('slug', $slug);

        return $tag ? response()->json($tag, 200) : response()->json(null, 404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsForTag(Request $request, $slug): JsonResponse
    {
        $site = $this->currentSite($request);
        $tag = Tag::query()
            ->where('site_id', $site->id)
            ->firstWhere('slug', $slug);

        return $tag ? response()->json(
            $tag->posts()
                ->where('canvas_posts.site_id', $site->id)
                ->published()
                ->with('topic', 'user')
                ->paginate(),
            200
        ) : response()->json(null, 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getTopics(Request $request): string
    {
        return Topic::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->get()
            ->toJson();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTopic(Request $request, $slug): JsonResponse
    {
        $topic = Topic::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->firstWhere('slug', $slug);

        return $topic ? response()->json($topic, 200) : response()->json(null, 404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsForTopic(Request $request, $slug): JsonResponse
    {
        $site = $this->currentSite($request);
        $topic = Topic::query()
            ->where('site_id', $site->id)
            ->firstWhere('slug', $slug);

        return $topic ? response()->json(
            $topic->posts()
                ->where('canvas_posts.site_id', $site->id)
                ->published()
                ->with('topic', 'user')
                ->paginate(),
            200
        ) : response()->json(null, 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showUser(Request $request, $id): JsonResponse
    {
        $user = User::query()
            ->where('site_id', $this->currentSite($request)->id)
            ->with(['posts' => function ($query) use ($request) {
                $query->where('site_id', $this->currentSite($request)->id)->published();
            }])
            ->find($id);

        return $user ? response()->json($user, 200) : response()->json(null, 404);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostsForUser(Request $request, $id): JsonResponse
    {
        $site = $this->currentSite($request);
        $user = User::query()
            ->where('site_id', $site->id)
            ->find($id);

        return $user ? response()->json(
            $user->posts()
                ->where('site_id', $site->id)
                ->published()
                ->with('user', 'topic')
                ->paginate(),
            200
        ) : response()->json(null, 200);
    }
}
