<template>
    <section>
        <page-header />

        <div class="mt-5">
            <div class="col-xl-8 offset-xl-2 col-lg-10 offset-lg-1 col-md-12 mt-3">
                <h1 class="font-serif">rv-Tampa's Blog</h1>
                <p class="lead text-secondary">What you need to know about RV camping in Florida</p>

                <main role="main" class="mt-5">
                    <div>
                        <h4 class="my-4 border-bottom mt-5 pb-2">
                            <span class="border-bottom border-dark pb-2">Posts</span>
                        </h4>

                        <div :key="`${index}-${post.id}`" v-for="(post, index) in posts">
                            <router-link
                                :to="{ name: 'show-post', params: { slug: post.slug } }"
                                class="text-decoration-none"
                            >
                                <div class="card mb-4 shadow">
                                    <div class="card-body px-0">
                                        <div class="container d-lg-inline-flex align-items-center">
                                            <div v-if="post.featured_image" class="col-12 col-lg-3 p-0">
                                                <img
                                                    :src="post.featured_image"
                                                    :alt="post.featured_image_caption"
                                                    class="rounded w-100"
                                                />
                                            </div>
                                            <section
                                                class="col-12 mt-3 mt-lg-0 px-0 px-lg-3"
                                                :class="post.featured_image ? 'col-lg-9' : ''"
                                            >
                                                <h5 class="card-title text-truncate mb-0">{{ post.title }}</h5>
                                                <p class="card-text text-truncate">{{ post.summary }}</p>
                                                <p class="card-text mb-0 text-secondary">
                                                    {{ post.user.name }}
                                                    <span v-if="post.topic.length"> in {{ post.topic[0].name }} </span>
                                                </p>
                                                <p class="card-text text-secondary">
                                                    {{formatDate(post.published_at)}} —
                                                    {{ post.read_time }}
                                                </p>
                                            </section>
                                        </div>
                                    </div>
                                </div>
                            </router-link>
                        </div>

                        <v-row v-if="!isReady" justify='center'>
                            <v-col cols='auto'>
                                <v-progress-circular
                                    color="primary"
                                    indeterminate
                                    ></v-progress-circular>
                            </v-col>
                        </v-row>
                    </div>
                </main>
            </div>
        </div>
    </section>
</template>

<script>
// import InfiniteLoading from 'vue-infinite-loading';
import NProgress from 'nprogress';
import PageHeader from '@/canvas-ui/components/PageHeaderComponent.vue';
import isEmpty from 'lodash/isEmpty';
import { formatDate } from './../../custom/dateUtils.js';

export default {
    name: 'all-posts',

    components: {
        PageHeader,
    },

    metaInfo() {
        return {
            title: 'Canvas',
        };
    },

    data() {
        return {
            page: 1,
            posts: [],
            isReady: false,
        };
    },

    async created() {
        await Promise.all([this.fetchPosts()]);
        this.isReady = true;
        NProgress.done();
    },

    methods: {
        fetchPosts($state) {
            this.isReady = false;
            // if ($state) {
                return this.request()
                    .get('/api/posts', {
                        params: {
                            page: this.page,
                        },
                    })
                    .then(({ data }) => {
                        if (!isEmpty(data) && !isEmpty(data.data)) {
                            this.page += 1;
                            this.posts.push(...data.data);
                        }
                        this.isReady = true;
                    })
                    .catch(() => {
                        this.isReady = true;
                         NProgress.done();
                    });
            // }
        },
        formatDate(dateString) {
          return formatDate(dateString);
        }
    },
};
</script>
