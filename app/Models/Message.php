<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'message', 'attachment_path', 'is_read'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->select('id', 'name', 'username', 'avatar');
    }
}