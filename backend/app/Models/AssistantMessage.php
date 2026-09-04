<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One turn of a public shop-assistant conversation, logged for product
 * insight ("what do customers actually ask?"). Written by AssistantController;
 * nothing reads it back in the app yet — it's a queryable record for now.
 */
class AssistantMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'user_email',
        'role',
        'content',
        'source',
    ];
}
