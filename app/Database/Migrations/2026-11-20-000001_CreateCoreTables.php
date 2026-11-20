<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateCoreTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'name'=>['type'=>'VARCHAR','constraint'=>120],
            'email'=>['type'=>'VARCHAR','constraint'=>190],
            'password_hash'=>['type'=>'VARCHAR','constraint'=>255],
            'role'=>['type'=>'ENUM','constraint'=>['admin','user'],'default'=>'user'],
            'status'=>['type'=>'ENUM','constraint'=>['active','suspended'],'default'=>'active'],
            'quota_bytes'=>['type'=>'BIGINT','unsigned'=>true,'default'=>10737418240],
            'used_bytes'=>['type'=>'BIGINT','unsigned'=>true,'default'=>0],
            'must_change_password'=>['type'=>'BOOLEAN','default'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],
            'updated_at'=>['type'=>'DATETIME','null'=>true]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'user_id'=>['type'=>'BIGINT','unsigned'=>true],
            'parent_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'original_name'=>['type'=>'VARCHAR','constraint'=>255],
            'stored_name'=>['type'=>'VARCHAR','constraint'=>80],
            'mime_type'=>['type'=>'VARCHAR','constraint'=>190],
            'extension'=>['type'=>'VARCHAR','constraint'=>20,'null'=>true],
            'size_bytes'=>['type'=>'BIGINT','unsigned'=>true],
            'storage_path'=>['type'=>'VARCHAR','constraint'=>500],
            'is_starred'=>['type'=>'BOOLEAN','default'=>false],
            'is_trashed'=>['type'=>'BOOLEAN','default'=>false],
            'created_at'=>['type'=>'DATETIME','null'=>true],
            'updated_at'=>['type'=>'DATETIME','null'=>true]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id','parent_id']);
        $this->forge->createTable('drive_files', true);
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'file_id'=>['type'=>'BIGINT','unsigned'=>true],
            'owner_id'=>['type'=>'BIGINT','unsigned'=>true],
            'shared_with_email'=>['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'token_hash'=>['type'=>'CHAR','constraint'=>64,'null'=>true],
            'permission'=>['type'=>'ENUM','constraint'=>['viewer','editor'],'default'=>'viewer'],
            'expires_at'=>['type'=>'DATETIME','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('file_id');
        $this->forge->createTable('file_shares', true);
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'user_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'action'=>['type'=>'VARCHAR','constraint'=>100],
            'details'=>['type'=>'TEXT','null'=>true],
            'ip_address'=>['type'=>'VARCHAR','constraint'=>45,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('activity_logs', true);
    }
    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
        $this->forge->dropTable('file_shares', true);
        $this->forge->dropTable('drive_files', true);
        $this->forge->dropTable('users', true);
    }
}
