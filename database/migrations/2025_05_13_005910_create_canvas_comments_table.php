<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCanvasCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('canvas_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->text('content');
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_url')->nullable();
            $table->string('author_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('post_id')
                  ->references('id')
                  ->on('canvas_posts')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')
                  ->on('canvas_users')
                  ->onDelete('set null');
                  
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('canvas_comments')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('canvas_comments');
    }
}