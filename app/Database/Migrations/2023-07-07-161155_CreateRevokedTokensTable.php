<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRevokedTokensTable extends Migration
{
    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ]
        ]);
        $this->forge->createTable('revoked_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('revoked_tokens');
    }

}
