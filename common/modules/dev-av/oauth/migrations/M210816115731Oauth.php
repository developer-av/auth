<?php

namespace DevAV\oauth\migrations;

use yii\db\Migration;

/**
 * Class M210816115731Oauth
 */
class M210816115731Oauth extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%oauth_clients}}', [
            'client_id' => $this->string(32)->notNull(),
            'client_secret' => $this->string(32),
            'grant_types' => $this->string(100)->notNull(),
            'user_id' => $this->integer(),
            'active' => $this->boolean()->defaultValue(true),
        ]);
        
        $this->addPrimaryKey('pk-client_id', '{{%oauth_clients}}', ['client_id']);
        $this->addForeignKey('fk-oauth_clients-user', '{{%oauth_clients}}', 'user_id','{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%oauth_access_tokens}}', [
            'id' => $this->bigPrimaryKey(),
            'access_token' => $this->string(40)->notNull(),
            'client_id' => $this->string(32)->notNull(),
            'user_id' => $this->integer()->notNull(),
            'expires' => $this->timestamp(),
            'active' => $this->boolean()->defaultValue(true),
        ]);
        
        $this->addForeignKey('fk-oauth_access_tokens-user', '{{%oauth_access_tokens}}', 'user_id','{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-oauth_access_tokens-client', '{{%oauth_access_tokens}}', 'client_id','{{%oauth_clients}}', 'client_id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%oauth_refresh_tokens}}', [
            'id' => $this->bigPrimaryKey(),
            'access_token_id' => $this->bigInteger()->notNull(),
            'refresh_token' => $this->string(40)->notNull(),
            'client_id' => $this->string(32)->notNull(),
            'user_id' => $this->integer()->notNull(),
            'expires' => $this->timestamp(),
            'active' => $this->boolean()->defaultValue(true),
        ]);

        $this->addForeignKey('fk-oauth_refresh_tokens-user', '{{%oauth_refresh_tokens}}', 'user_id','{{%user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-oauth_refresh_tokens-client', '{{%oauth_refresh_tokens}}', 'client_id','{{%oauth_clients}}', 'client_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-oauth_refresh_tokens-oauth_access_tokens', '{{%oauth_refresh_tokens}}', 'access_token_id','{{%oauth_access_tokens}}', 'id', 'CASCADE', 'CASCADE');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "M210816115731Oauth cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M210816115731Oauth cannot be reverted.\n";

        return false;
    }
    */
}
