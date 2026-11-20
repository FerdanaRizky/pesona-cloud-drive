<?php
namespace App\Models;

use CodeIgniter\Model;

class DriveFolderModel extends Model
{
    protected $table = 'drive_folders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'parent_id', 'name', 'is_trashed'];
    protected $useTimestamps = true;
}
