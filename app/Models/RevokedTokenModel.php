<?php

namespace App\Models;

use CodeIgniter\Model;

class RevokedTokenModel extends Model
{
    protected $table = 'revoked_tokens';
    protected $primaryKey = 'id';
    protected $allowedFields = ['token'];
}
