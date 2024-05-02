import AllPosts from '@/canvas-ui/views/AllPosts.vue';
import AllTags from '@/canvas-ui/views/AllTags.vue';
import AllTopics from '@/canvas-ui/views/AllTopics.vue';
import ShowPost from '@/canvas-ui/views/ShowPost.vue';
import ShowTag from '@/canvas-ui/views/ShowTag.vue';
import ShowTopic from '@/canvas-ui/views/ShowTopic.vue';
import ShowUser from '@/canvas-ui/views/ShowUser.vue';

export default [
    {
        path: '/',
        name: 'posts',
        component: AllPosts,
    },
    {
        path: '/posts/:slug',
        name: 'show-post',
        component: ShowPost,
    },
    {
        path: '/tags',
        name: 'tags',
        component: AllTags,
    },
    {
        path: '/tags/:slug',
        name: 'show-tag',
        component: ShowTag,
    },
    {
        path: '/topics',
        name: 'topics',
        component: AllTopics,
    },
    {
        path: '/topics/:slug',
        name: 'show-topic',
        component: ShowTopic,
    },
    {
        path: '/:id',
        name: 'show-user',
        component: ShowUser,
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'catch-all',
        redirect: '/canvas-ui',
    },
];
