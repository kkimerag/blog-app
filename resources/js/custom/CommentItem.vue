<template>
  <div class="comment-wrapper mt-4">
    <v-card flat class="pa-0">
      <div class="d-flex">
        <!-- Avatar -->
        <v-avatar size="40" color="grey-lighten-3" class="mr-3 mt-1">
          <v-img
            v-if="commentAuthorAvatar"
            :src="commentAuthorAvatar"
            :alt="commentAuthorName"
          ></v-img>
          <span v-else class="text-h6">{{ authorInitials }}</span>
        </v-avatar>

        <!-- Comment content -->
        <div class="flex-grow-1">
          <div class="d-flex align-center">
            <span class="font-weight-medium">{{ commentAuthorName }}</span>
            <v-chip
              v-if="comment.user && (comment.user.isAdmin || comment.user.isEditor)"
              size="x-small"
              color="primary"
              class="ml-2"
              variant="flat"
            >
              {{ comment.user.isAdmin ? 'Admin' : 'Editor' }}
            </v-chip>
            <span class="text-caption text-grey ml-3">{{ formatDate(comment.created_at) }}</span>
          </div>

          <v-card-text class="px-0 py-2 text-body-1">
            {{ comment.content }}
          </v-card-text>

          <!-- Comment actions -->
          <div class="actions mb-2">
            <v-btn
              size="small"
              variant="text"
              prepend-icon="mdi-reply"
              @click="$emit('reply', comment)"
            >
              Reply
            </v-btn>

            <template v-if="canModify">
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-pencil"
                @click="$emit('edit', comment.id, comment.content)"
              >
                Edit
              </v-btn>
              <v-btn
                size="small"
                variant="text"
                color="error"
                prepend-icon="mdi-delete"
                @click="$emit('delete', comment.id)"
              >
                Delete
              </v-btn>
            </template>
          </div>

          <!-- Replies -->
          <div v-if="comment.replies && comment.replies.length > 0" class="replies-container ml-6 mt-4">
            <v-divider class="mb-3"></v-divider>
            
            <template v-for="(reply, index) in comment.replies" :key="reply.id">
              <div class="reply-item mb-3">
                <div class="d-flex">
                  <!-- Reply avatar -->
                  <v-avatar size="32" color="grey-lighten-2" class="mr-3 mt-1">
                    <v-img
                      v-if="getReplyAuthorAvatar(reply)"
                      :src="getReplyAuthorAvatar(reply)"
                      :alt="getReplyAuthorName(reply)"
                    ></v-img>
                    <span v-else class="text-caption">{{ getReplyAuthorInitials(reply) }}</span>
                  </v-avatar>

                  <!-- Reply content -->
                  <div class="flex-grow-1">
                    <div class="d-flex align-center">
                      <span class="font-weight-medium">{{ getReplyAuthorName(reply) }}</span>
                      <v-chip
                        v-if="reply.user && (reply.user.isAdmin || reply.user.isEditor)"
                        size="x-small"
                        color="primary"
                        class="ml-2" 
                        variant="flat"
                      >
                        {{ reply.user.isAdmin ? 'Admin' : 'Editor' }}
                      </v-chip>
                      <span class="text-caption text-grey ml-3">{{ formatDate(reply.created_at) }}</span>
                    </div>

                    <v-card-text class="px-0 py-2 text-body-2">
                      {{ reply.content }}
                    </v-card-text>

                    <!-- Reply actions -->
                    <div class="actions mb-2" v-if="canModifyReply(reply)">
                      <v-btn
                        size="x-small"
                        variant="text"
                        prepend-icon="mdi-pencil"
                        @click="$emit('edit', reply.id, reply.content)"
                      >
                        Edit
                      </v-btn>
                      <v-btn
                        size="x-small"
                        variant="text"
                        color="error"
                        prepend-icon="mdi-delete"
                        @click="$emit('delete', reply.id)"
                      >
                        Delete
                      </v-btn>
                    </div>
                  </div>
                </div>
              </div>
              <v-divider v-if="index < comment.replies.length - 1" class="my-3"></v-divider>
            </template>
          </div>
        </div>
      </div>
    </v-card>
  </div>
</template>

<script>
export default {
  name: 'CommentItem',
  
  props: {
    comment: {
      type: Object,
      required: true
    },
    isAuthenticated: {
      type: Boolean,
      default: false
    },
    currentUser: {
      type: Object,
      default: null
    }
  },
  
  computed: {
    commentAuthorName() {
      return this.comment.user 
        ? this.comment.user.name 
        : this.comment.author_name || 'Anonymous';
    },
    
    commentAuthorAvatar() {
      return this.comment.user 
        ? (this.comment.user.avatar || this.comment.user.default_avatar) 
        : null;
    },
    
    authorInitials() {
      const name = this.commentAuthorName;
      if (!name || name === 'Anonymous') return 'A';
      
      const parts = name.split(' ');
      if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
      return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    },
    
    canModify() {
      if (!this.isAuthenticated || !this.currentUser) return false;
      
      // User can modify their own comments
      if (this.comment.user && this.comment.user.id === this.currentUser.id) return true;
      
      // Admins and editors can modify any comment
      return this.currentUser.isAdmin || this.currentUser.isEditor;
    }
  },
  
  methods: {
    formatDate(dateString) {
      const date = new Date(dateString);
      
      // Get relative time if less than 7 days old
      const now = new Date();
      const diffTime = Math.abs(now - date);
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays < 1) {
        const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
        if (diffHours < 1) {
          const diffMinutes = Math.floor(diffTime / (1000 * 60));
          if (diffMinutes < 1) return 'just now';
          return `${diffMinutes} ${diffMinutes === 1 ? 'minute' : 'minutes'} ago`;
        }
        return `${diffHours} ${diffHours === 1 ? 'hour' : 'hours'} ago`;
      } else if (diffDays < 7) {
        return `${diffDays} ${diffDays === 1 ? 'day' : 'days'} ago`;
      }
      
      // Format as date for older comments
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return date.toLocaleDateString(undefined, options);
    },
    
    getReplyAuthorName(reply) {
      return reply.user 
        ? reply.user.name 
        : reply.author_name || 'Anonymous';
    },
    
    getReplyAuthorAvatar(reply) {
      return reply.user 
        ? (reply.user.avatar || reply.user.default_avatar) 
        : null;
    },
    
    getReplyAuthorInitials(reply) {
      const name = this.getReplyAuthorName(reply);
      if (!name || name === 'Anonymous') return 'A';
      
      const parts = name.split(' ');
      if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
      return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    },
    
    canModifyReply(reply) {
      if (!this.isAuthenticated || !this.currentUser) return false;
      
      // User can modify their own replies
      if (reply.user && reply.user.id === this.currentUser.id) return true;
      
      // Admins and editors can modify any reply
      return this.currentUser.isAdmin || this.currentUser.isEditor;
    }
  }
};
</script>

<style scoped>
.replies-container {
  border-left: 2px solid rgba(0, 0, 0, 0.05);
  padding-left: 16px;
}
</style>