<?php
namespace App\\Database\\Migrations;
use CodeIgniter\\Database\\Migration;
class CreateRegistrationOtps extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true],
            'name'=>['type'=>'VARCHAR','constraint'=>120],
            'email'=>['type'=>'VARCHAR','constraint'=>190],
            'phone'=>['type'=>'VARCHAR','constraint'=>20],
            'password_hash'=>['type'=>'VARCHAR','constraint'=>255],
            'otp_hash'=>['type'=>'CHAR','constraint'=>64],
            'expires_at'=>['type'=>'DATETIME'],
            'used_at'=>['type'=>'DATETIME','null'=>true],
            'attempts'=>['type'=>'SMALLINT','unsigned'=>true,'default'=>0],
            'ip_address'=>['type'=>'VARCHAR','constraint'=>45,'null'=>true],
            'created_at'=>['type'=>'DATETIME'],
        ]);
        $this->forge->addKey('id',true);
        $this->forge->addKey(['email','created_at']);
        $this->forge->addKey(['ip_address','created_at']);
        $this->forge->createTable('registration_otps',true);
    }
    public function down(){ $this->forge->dropTable('registration_otps',true); }
}
