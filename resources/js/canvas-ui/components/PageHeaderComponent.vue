<template>
    <div class="border-bottom">
        <div class="col-xl-8 offset-xl-2 col-lg-10 offset-lg-1 col-md-12">
            <nav class="navbar d-flex px-0 py-1">
                <router-link :to="{ name: 'posts' }" class="navbar-brand hover font-weight-bolder font-serif mr-3">
                    rv-Tampa's Blog
                </router-link>
                <div class="mr-auto border-left pl-1">
                    <router-link :to="{ name: 'tags' }" class="btn btn-link py-0 text-decoration-none">
                        Tags
                    </router-link>
                    <router-link :to="{ name: 'topics' }" class="btn btn-link py-0 text-decoration-none">
                        Topics
                    </router-link>
                </div>

                <slot v-if="user" name="options" />

                <div v-if="user" class="dropdown ml-3">
                    <v-menu

                    >
                          <template v-slot:activator="{ props }">
                            <v-btn
                              v-bind="props"
                              icon
                              color='white'
                              elevation="0"
                            >
                                <v-avatar>
                                  <v-img
                                    :alt="user.name"
                                    :src="user.avatar || user.default_avatar"
                                  ></v-img>
                                </v-avatar>
                            </v-btn>
                          </template>
                          <v-list
                          >
                            <v-list-item
                                :prepend-avatar="user.avatar || user.default_avatar"
                                :subtitle="user.email"
                                :title="user.name"
                              >
                              </v-list-item>
                              <v-divider></v-divider>
                            <v-list-item>
                              <v-list-item-title>
                                <a :href="`/${canvasPath}/users/${user.id}/edit`" class="dropdown-item">Your profile</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-list-item>
                              <v-list-item-title>
                                <a :href="`/${canvasPath}/posts`" class="dropdown-item">Posts</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-list-item>
                              <v-list-item-title>
                                <a v-if="isAdmin" :href="`/${canvasPath}/users`" class="dropdown-item">Users</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-list-item>
                              <v-list-item-title>
                                <a v-if="isAdmin" :href="`/${canvasPath}/tags`" class="dropdown-item">Tags</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-list-item>
                              <v-list-item-title>
                                <a v-if="isAdmin" :href="`/${canvasPath}/topics`" class="dropdown-item">Topics</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-list-item>
                              <v-list-item-title>
                                <a :href="`/${canvasPath}/stats`" class="dropdown-item">Stats</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-divider></v-divider>
                            <v-list-item>
                              <v-list-item-title>
                                <a :href="`/${canvasPath}/settings`" class="dropdown-item">Settings</a>
                              </v-list-item-title>
                            </v-list-item>
                            <v-list-item>
                              <v-list-item-title>
                                <a href="" class="dropdown-item" @click.prevent="logout"> Sign out </a>
                              </v-list-item-title>
                            </v-list-item>
                          </v-list>
                        </v-menu>
                </div>

                <a v-if="!user" :href="`/${canvasPath}/login`" class="btn btn-link text-decoration-none">Sign in</a>
            </nav>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'page-header-component',

    data() {
        return {
            user: CanvasUI.user, // eslint-disable-line no-undef
            canvasPath: CanvasUI.canvasPath, // eslint-disable-line no-undef
        };
    },

    methods: {
        logout() {
            axios.get(`/${this.canvasPath}/logout`).then(() => {
                window.location.href = `/${this.canvasPath}/login`;
            });
        },
    },
};
</script>
