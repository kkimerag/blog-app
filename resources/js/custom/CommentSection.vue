<template>
  <v-card class="mt-10 mb-6 pa-4" flat>
    <v-card-title class="font-weight-bold text-h5">Comments</v-card-title>
    
    <div v-if="errorState" class="text-center py-6">
      <v-alert type="warning" class="mx-auto" max-width="550">
        <p class="mb-0">Comments are temporarily unavailable. Please try again later.</p>
      </v-alert>
    </div>
    
    <template v-else>
      <!-- Comment form -->
      <v-card class="mb-5 pa-3" variant="outlined">
        <v-card-title class="text-body-1 font-weight-medium">Leave a comment</v-card-title>
        <v-form @submit.prevent="submitComment" ref="commentForm">
          <v-row class="pa-3">
            <v-col cols="12" v-if="!isAuthenticated">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.trim="newComment.author_name"
                    label="Name"
                    :rules="[rules.required]"
                    variant="outlined"
                    density="comfortable"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model.trim="newComment.author_email"
                    label="Email"
                    :rules="[rules.required, rules.email]"
                    variant="outlined"
                    density="comfortable"
                  ></v-text-field>
                </v-col>
              </v-row>
            </v-col>
            <v-col cols="12">
              <v-textarea
                v-model.trim="newComment.content"
                label="Your comment"
                :rules="[rules.required]"
                variant="outlined"
                rows="3"
                auto-grow
                counter
                max-length="3000"
              ></v-textarea>
            </v-col>
            <v-col cols="12" class="d-flex justify-end">
              <v-btn
                color="primary"
                type="submit"
                :loading="isSubmitting"
                :disabled="isSubmitting"
              >
                Post Comment
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </v-card>

      <!-- Comments list -->
      <div v-if="loading" class="d-flex justify-center my-6">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
      </div>

      <template v-else>
        <div v-if="comments.length === 0" class="text-center py-8 text-body-1 text-medium-emphasis">
          Be the first to comment on this post!
        </div>
        
        <div v-else>
          <v-card-subtitle class="pb-0 pl-0">
            {{ comments.length }} {{ comments.length === 1 ? 'Comment' : 'Comments' }}
          </v-card-subtitle>
          
          <template v-for="(comment, index) in comments" :key="comment.id">
            <comment-item 
              :comment="comment" 
              :is-authenticated="isAuthenticated" 
              :current-user="currentUser"
              @reply="handleReply"
              @edit="handleEdit"
              @delete="handleDelete"
            ></comment-item>
            <v-divider v-if="index < comments.length - 1" class="my-3"></v-divider>
          </template>
        </div>
        
        <v-pagination
          v-if="pagination.last_page > 1"
          v-model="pagination.current_page"
          :length="pagination.last_page"
          @update:model-value="fetchComments"
          class="mt-6"
        ></v-pagination>
      </template>
    </template>

    <!-- Reply modal -->
    <v-dialog v-model="replyDialog" max-width="600px">
      <v-card>
        <v-card-title>Reply to comment</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="submitReply" ref="replyForm">
            <v-row>
              <v-col cols="12" v-if="!isAuthenticated">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.trim="replyComment.author_name"
                      label="Name"
                      :rules="[rules.required]"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.trim="replyComment.author_email"
                      label="Email"
                      :rules="[rules.required, rules.email]"
                      variant="outlined"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model.trim="replyComment.content"
                  label="Your reply"
                  :rules="[rules.required]"
                  variant="outlined"
                  rows="3"
                  auto-grow
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="replyDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            variant="flat" 
            @click="submitReply"
            :loading="isSubmitting"
            :disabled="isSubmitting"
          >
            Reply
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Notification snackbar -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
    >
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn
          color="white"
          variant="text"
          @click="snackbar.show = false"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import CommentItem from './CommentItem.vue';

export default {
  name: 'CommentSection',
  
  components: {
    CommentItem
  },
  
  props: {
    postId: {
      type: String,
      required: true
    }
  },
  
  emits: ['error'],
  
  data() {
    return {
      comments: [],
      loading: true,
      isSubmitting: false,
      errorState: false,
      
      newComment: {
        post_id: this.postId,
        content: '',
        author_name: '',
        author_email: '',
        parent_id: null
      },
      
      replyComment: {
        post_id: this.postId,
        content: '',
        author_name: '',
        author_email: '',
        parent_id: null
      },
      
      parentComment: null,
      replyDialog: false,
      
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      },
      
      snackbar: {
        show: false,
        text: '',
        color: 'success'
      },
      
      rules: {
        required: v => !!v || 'This field is required',
        email: v => /.+@.+\..+/.test(v) || 'E-mail must be valid'
      }
    };
  },
  
  computed: {
    isAuthenticated() {
      return !!this.CanvasUI.user;
    },
    
    currentUser() {
      return this.CanvasUI.user;
    },
    
    isAdmin() {
      return this.isAuthenticated && this.CanvasUI.user.isAdmin;
    },
    
    isEditor() {
      return this.isAuthenticated && this.CanvasUI.user.isEditor;
    }
  },
  
  created() {
    this.fetchComments();
  },
  
  methods: {
    fetchComments() {
      this.loading = true;
      
      this.request()
        .get('/api/comments', {
          params: {
            post_id: this.postId,
            page: this.pagination.current_page,
            root_only: true,
            approved: true
          }
        })
        .then(response => {
          this.comments = response.data.data;
          this.pagination = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total
          };
        })
        .catch(error => {
          console.error('Error fetching comments:', error);
          this.errorState = true;
          this.$emit('error', error);
          this.showSnackbar('Failed to load comments', 'error');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    
    async submitComment() {
      try {
        const { valid } = await this.$refs.commentForm.validate();
        if (!valid) return;
        
        this.isSubmitting = true;
        
        await this.request()
          .post('/api/comments', this.newComment)
          .then(() => {
            this.showSnackbar(
              this.isAuthenticated 
                ? 'Comment posted successfully' 
                : 'Comment submitted and awaiting approval',
              'success'
            );
            
            // Reset form
            this.newComment.content = '';
            this.newComment.author_name = '';
            this.newComment.author_email = '';
            this.$refs.commentForm.reset();
            
            // Refresh comments if user is authenticated (auto-approved)
            if (this.isAuthenticated && (this.isAdmin || this.isEditor)) {
              this.fetchComments();
            }
          })
          .catch(error => {
            console.error('Error posting comment:', error);
            this.showSnackbar('Failed to post comment', 'error');
          });
      } catch (error) {
        console.error('Error in submit comment:', error);
        this.showSnackbar('Failed to process comment', 'error');
      } finally {
        this.isSubmitting = false;
      }
    },
    
    handleReply(comment) {
      this.parentComment = comment;
      this.replyComment.parent_id = comment.id;
      this.replyDialog = true;
    },
    
    async submitReply() {
      try {
        const { valid } = await this.$refs.replyForm.validate();
        if (!valid) return;
        
        this.isSubmitting = true;
        
        await this.request()
          .post('/api/comments', this.replyComment)
          .then(() => {
            this.showSnackbar(
              this.isAuthenticated 
                ? 'Reply posted successfully' 
                : 'Reply submitted and awaiting approval',
              'success'
            );
            
            // Close dialog and reset form
            this.replyDialog = false;
            this.replyComment.content = '';
            this.replyComment.author_name = '';
            this.replyComment.author_email = '';
            this.replyComment.parent_id = null;
            
            // Refresh comments if user is authenticated (auto-approved)
            if (this.isAuthenticated && (this.isAdmin || this.isEditor)) {
              this.fetchComments();
            }
          })
          .catch(error => {
            console.error('Error posting reply:', error);
            this.showSnackbar('Failed to post reply', 'error');
          });
      } catch (error) {
        console.error('Error in submit reply:', error);
        this.showSnackbar('Failed to process reply', 'error');
      } finally {
        this.isSubmitting = false;
      }
    },
    
    handleEdit(commentId, content) {
      // Implement edit functionality if needed
      console.log('Edit comment', commentId, content);
    },
    
    handleDelete(commentId) {
      if (confirm('Are you sure you want to delete this comment?')) {
        this.request()
          .delete(`/api/comments/${commentId}`)
          .then(() => {
            this.showSnackbar('Comment deleted successfully', 'success');
            this.fetchComments();
          })
          .catch(error => {
            console.error('Error deleting comment:', error);
            this.showSnackbar('Failed to delete comment', 'error');
          });
      }
    },
    
    showSnackbar(text, color = 'success') {
      this.snackbar.text = text;
      this.snackbar.color = color;
      this.snackbar.show = true;
    },
    
    request() {
      return axios.create({
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        },
        timeout: 10000 // Add timeout to prevent long hanging requests
      });
    }
  }
};
</script>

<style scoped>
.pending-badge {
  position: absolute;
  top: 12px;
  right: 12px;
}
</style>