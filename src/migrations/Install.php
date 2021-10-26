<?php
/**
 * @link https://dukt.net/videos/
 * @copyright Copyright (c) 2021, Dukt
 * @license https://github.com/dukt/videos/blob/v2/LICENSE.md
 */

namespace dukt\videos\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration class.
 *
 * @author Dukt <support@dukt.net>
 * @since 2.0.8
 */
class Install extends Migration
{
    /**
     * @var string The database driver to use
     *
     * @since 2.0.8
     */
    public $driver;

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return bool return a false value to indicate the migration fails
     *              and should not proceed further. All other return values mean the migration succeeds.
     *
     * @since 2.0.8
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return bool return a false value to indicate the migration fails
     *              and should not proceed further. All other return values mean the migration succeeds.
     *
     * @since 2.0.8
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * Creates the tables needed for the Records used by the plugin.
     *
     * @return void
     *
     * @since 2.0.8
     */
    protected function createTables()
    {
        $this->createTable(
            '{{%videos_tokens}}',
            [
                'id' => $this->primaryKey(),
                'gateway' => $this->string()->notNull(),
                'accessToken' => $this->text(),

                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
    }

    /**
     * Creates the indexes needed for the Records used by the plugin.
     *
     * @return void
     *
     * @since 2.0.8
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%videos_tokens}}', 'gateway', true);
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin.
     *
     * @return void
     *
     * @since 2.0.8
     */
    protected function addForeignKeys()
    {
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     *
     * @since 2.0.8
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin.
     *
     * @return void
     *
     * @since 2.0.8
     */
    protected function removeTables()
    {
        $this->dropTable('{{%videos_tokens}}');
    }
}
