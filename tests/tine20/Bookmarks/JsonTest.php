<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Bookmarks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test class for Bookmarks_Json
 */
class Bookmarks_JsonTest extends TestCase
{
    public function testBookmarkApi()
    {
        $bookmark = $this->_testSimpleRecordApi('Bookmark', 'url', 'description', false, [
            Bookmarks_Model_Bookmark::FLDS_URL => 'https://some.nice.url'
        ]);
        self::assertEquals('some.nice.url', $bookmark[Bookmarks_Model_Bookmark::FLDS_NAME]);
    }
}
