<?php
// database/migrations/xxxx_xx_xx_update_chat_id_nullable_in_conversations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateChatIdNullableInConversationsTable extends Migration
{
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('chat_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('chat_id')->nullable(false)->change();
        });
    }
}
