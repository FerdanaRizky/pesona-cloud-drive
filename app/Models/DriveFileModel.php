<?php
namespace App\Models;
use CodeIgniter\Model;
class DriveFileModel extends Model
{
    protected $table = 'drive_files';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id','parent_id','original_name','stored_name','mime_type','extension','size_bytes','storage_path','is_starred','is_trashed'];
    protected $useTimestamps = true;
}
