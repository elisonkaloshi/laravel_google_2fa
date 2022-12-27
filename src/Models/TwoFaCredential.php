<?php

namespace Elison\GoogleAuthenticator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoFaCredential extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'secret_key'];

}
