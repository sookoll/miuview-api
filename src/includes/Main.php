<?php

namespace App;

/*
 * Miuview API admin
 * main class to process data
 *
 * Creator: Mihkel Oviir
 * 08.2011
 *
 */

class Main
{
    
    private array $output;

    public function __construct(private $app)
    {

        $tmp = [];

        // check cookie login
        if ($this->app->sess->miuview_admin_in !== true && isset($_COOKIE['remember_me']) && $_COOKIE['remember_me'] === md5($this->app->conf['USER'])) {
            // make it longer
            setcookie('remember_me', md5($this->app->conf['USER']), time() + 60 * 60 * 24 * 30, '/');
            $this->app->sess->miuview_admin_in = true;
        }

        // if user is logged in
        if ($this->app->sess->miuview_admin_in === true) {
            $html = "{$this->app->conf['PATH_TMPL']}/{$this->app->conf['TEMPLATE']}/html/admin.html";
            $tmp['content-body'] = $this->app->func->replace_tags($html, []);
            $tmp['user-login-js'] = 'admin';
            $tmp['content-header'] = 'Adminni';
            $tmp['logout'] = ' [ <a href="#" id="logout-submit">Logi välja</a> ]';
        }
        // if not
        else {
            $html = "{$this->app->conf['PATH_TMPL']}/{$this->app->conf['TEMPLATE']}/html/login.html";
            $tmp['content-body'] = $this->app->func->parseFile($html);
            $tmp['user-login-js'] = 'main';
            $tmp['content-header'] = 'Sisene';
            $tmp['logout'] = '';
        }

        $this->output = $tmp;
    }

    // check albums against db and thumbs
    public function checkContent(): void
    {
        $tmp['content']['status'] = '0';
        
        if ($this->app->sess->miuview_admin_in === true && file_exists($this->app->conf['PATH_ALBUMS'])) {
            // read gallery content into array
            $albums = $this->folder2array($this->app->conf['PATH_ALBUMS']);

            // read albums in db into array
            $db = $this->loadDB2array();

            // read cache content into array
            $cache = $this->folder2array2($this->app->conf['PATH_CACHE']);

            // overleft in cache, delete
            list($a['cache_no_albums'], $a['cache_no_items']) = $this->compareArrays($cache, $albums, 'html');

            // not exist in db, insert into db
            list($a['albums_no_db'], $a['items_no_db']) = $this->compareArrays($albums, $db, 'html');

            // overleft in db, delete it in db
            list($a['db_no_albums'], $a['db_no_items']) = $this->compareArrays($db, $albums, 'html');

            // wrong orientation
            //$a['orientation'] = $this->checkOrientation(PATH_ALBUMS);

            $tmp['content']['status'] = '1';

            foreach ($a as $i) {
                if ($i) {
                    $tmp['content']['status'] = '2';
                }
            }

            $tmp['content']['data'] = $a;
        }

        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    public function submitCheck(): void
    {
        $tmp = array(
            'content_type' => 'json',
            'content' => array(
                'status' => $this->manageGallery(),
            )
        );
        $this->output = $tmp;
    }

    private function manageGallery(): int
    {
        $status = 0;
        if (
            $this->app->sess->miuview_admin_in === true &&
            file_exists($this->app->conf['PATH_ALBUMS']) &&
            file_exists($this->app->conf['PATH_CACHE'])
        ) {
            // format names
            $this->formatNames($this->app->conf['PATH_ALBUMS']);
            // read gallery content into array
            $albums = $this->folder2array($this->app->conf['PATH_ALBUMS']);
            // read albums in db into array
            $db = $this->loadDB2array();
            // read thumbs albums into array
            $cache = $this->folder2array2($this->app->conf['PATH_CACHE']);
            // overleft in thumbs, delete
            list($a['cache_no_albums'], $a['cache_no_items']) = $this->compareArrays($cache, $albums, 'array');
            // not exist in db, insert into db
            list($a['albums_no_db'], $a['items_no_db']) = $this->compareArrays($albums, $db, 'array');
            // overleft in db, delete it in db
            list($a['db_no_albums'], $a['db_no_items']) = $this->compareArrays($db, $albums, 'array');

            // delete database overlefts
            foreach ($a['db_no_albums'] as $album) {
                $q = "DELETE FROM {$this->app->conf['TBL_ITEMS']} WHERE album='$album'";
                if ($this->app->func->makeQuery($q)) {
                    $q = "DELETE FROM {$this->app->conf['TBL_ALBUMS']} WHERE album='$album'";
                    $this->app->func->makeQuery($q);
                }
            }

            foreach ($a['db_no_items'] as $akey => $album) {
                foreach ($album as $item) {
                    $q = "DELETE FROM {$this->app->conf['TBL_ITEMS']} WHERE album='$akey' AND item='$item'";
                    $this->app->func->makeQuery($q);
                }
            }

            // add new stuff to database
            foreach ($a['items_no_db'] as $akey => $album) {
                foreach ($album as $item) {
                    if ($i = $this->app->func->getType("{$this->app->conf['PATH_ALBUMS']}/$akey/$item")) {
                        $this->insertItem($i['type'], $akey, $item);
                    }
                }
            }

            foreach ($a['albums_no_db'] as $album) {
                $q = "INSERT INTO {$this->app->conf['TBL_ALBUMS']} (album, sort, added)" .
                    " SELECT '$album', CASE WHEN (SELECT COUNT(album) FROM {$this->app->conf['TBL_ALBUMS']}) = 0 THEN 0" .
                    " ELSE (SELECT MAX(sort) + 1 FROM {$this->app->conf['TBL_ALBUMS']}) END, NOW()";
                if ($this->app->func->makeQuery($q)) {
                    foreach ($albums[$album] as $item) {
                        if ($i = $this->app->func->getType("{$this->app->conf['PATH_ALBUMS']}/$album/$item")) {
                            $this->insertItem($i['type'], $album, $item);
                        }
                    }
                    // add first item as thumb
                    $q = "UPDATE {$this->app->conf['TBL_ALBUMS']} " .
                        " SET thumb = (SELECT item FROM {$this->app->conf['TBL_ITEMS']}" .
                        " WHERE album='$album' ORDER BY sort LIMIT 1) WHERE album='$album'";
                    $this->app->func->makeQuery($q);
                }
            }

            // delete cache overlefts
            foreach ($a['cache_no_items'] as $akey => $album) {
                foreach ($album as $item) {
                    $this->deleteCacheItem($akey, $item);
                }
            }

            foreach ($a['cache_no_albums'] as $album) {
                $this->app->func->removeDir($this->app->conf['PATH_CACHE'] . $album);
            }

            // remove all empty folders in cache
            $albums = scandir($this->app->conf['PATH_CACHE']);
            if (count($albums) > 2) {
                foreach ($albums as $album) {
                    if (
                        $album !== '.' &&
                        $album !== '..' &&
                        file_exists("{$this->app->conf['PATH_CACHE']}/$album") &&
                        is_dir("{$this->app->conf['PATH_CACHE']}/$album")
                    ) {
                        $this->app->func->RemoveEmptySubFolders("{$this->app->conf['PATH_CACHE']}/$album");
                    }
                }
            }

            // check if album thumb exist, if not then remove it
            $q = "SELECT * FROM {$this->app->conf['TBL_ALBUMS']} ORDER BY album";
            if ($result = $this->app->func->makeQuery($q)) {
                while ($a = $result->fetch_assoc()) {
                    $q = "SELECT item FROM {$this->app->conf['TBL_ITEMS']} WHERE album = '{$a['album']}' AND item='{$a['thumb']}'";
                    if ($result2 = $this->app->func->makeQuery($q)) {
                        if (!$result2->num_rows) {
                            $q = "UPDATE {$this->app->conf['TBL_ALBUMS']} SET thumb='' WHERE album='{$a['album']}'";
                            $this->app->func->makeQuery($q);
                        }
                    }
                }
            }

            $status = 1;
        }
        return $status;
    }

    // load gallery
    public function loadGallery()
    {
        $tmp['content']['status'] = '0';
        $tmp['content']['data'] = '';
        $hash = md5($this->app->conf['SECURITY_KEY']);

        if ($this->app->sess->miuview_admin_in === true) {
            if ($albums = $this->app->func->getAlbums()) {
                $html = "{$this->app->conf['PATH_TMPL']}/{$this->app->conf['TEMPLATE']}/html/album.html";
                foreach ($albums as $row) {
                    $items = $this->app->func->getItems($row['album']);
                    $a['album'] = $row['album'];
                    $a['name'] = $row['title'] !== '' ? $row['title'] : $row['album'];
                    $a['thumb'] = $row['thumb'] !== ''
                        ? $this->app->conf['URL'] . '?request=getimage&album=' . $row['album'] . '&item=' . $row['thumb'] . '&size=100&mode=square&key=' . $hash
                        : '{_def-tmpl_}images/album.png';
                    $a['pics'] = count($items[$row['album']]);
                    $a['public'] = $row['public'] === 1 ? 'checked="checked"' : '';
                    $tmp['content']['data'] .= $this->app->func->replace_tags($html, $a);
                }
            }
            $tmp['content']['key'] = $hash;
            $tmp['content']['url'] = $this->app->conf['URL'] . '?request=getalbum&album=*&thsize=' . $this->app->conf['TH_SIZE'] . '&key=';
            $tmp['content']['status'] = '1';
        }
        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // submit gallery
    public function submitGallery()
    {
        $tmp['content']['status'] = '0';

        if ($this->app->sess->miuview_admin_in === true) {
            $albums = $this->app->func->getAlbums();
            foreach ($data as $k => $v) {
                if (array_key_exists('publc', $v) && $v['publc'] === 'true') {
                    $public = 1;
                } else {
                    $public = 0;
                }
                if ($v['name'] === $albums[$k]['title'] && $v['sort'] === $albums[$k]['sort'] && $public === $albums[$k]['public']) {
                    continue;
                } else {
                    $q = "UPDATE " . TBL_ALBUMS . " SET title='" . htmlentities($v['name'], ENT_QUOTES) . "',sort=" . $v['sort'] . ",public=" . $public . " WHERE album='" . $k . "'";
                    if ($this->app->func->makeQuery($q)) {
                        $tmp['content']['status'] = '1';
                    } else {
                        $tmp['content']['status'] = '0';
                    }
                }
            }
        }

        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // load album
    public function loadAlbum()
    {
        $tmp['content']['status'] = '0';
        $tmp['content']['data'] = '';

        if ($this->app->sess->miuview_admin_in === true) {
            if ($items = $this->app->func->getItems($album)) {
                $html = PATH_TMPL . TEMPLATE . '/html/item.html';
                $albums = $this->app->func->getAlbums($album);
                foreach ($items[$album] as $row) {
                    $a['item'] = $row['item'];
                    $a['thumb'] = URL . '?request=getimage&album=' . $row['album'] . '&item=' . $row['item'] . '&size=' . TH_SIZE . '&mode=square&key=' . md5(SECURITY_KEY);
                    $a['album-thumb'] = $row['item'] === $albums[$album]['thumb'] ? 'checked' : '';
                    $a['width'] = $a['height'] = TH_SIZE;
                    $tmp['content']['data'] .= $this->app->func->replace_tags($html, $a);
                }
            }
            $tmp['content']['url'] = URL . '?request=getitem&album=' . $album . '&item=*&size=' . ITEM_SIZE . '&thsize=' . TH_SIZE . '&key=';
            $tmp['content']['title'] = isset($albums) ? $albums[$album]['title'] : '';
            $tmp['content']['status'] = '1';
        }
        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // submit album
    public function submitAlbum()
    {
        $tmp['content']['status'] = '0';

        if ($this->app->sess->miuview_admin_in === true) {
            $albums = $this->app->func->getAlbums($album);
            if ($albums[$album]['thumb'] !== $thumb) {
                $q = "UPDATE " . TBL_ALBUMS . " SET thumb='" . $thumb . "' WHERE album='" . $album . "'";
                if ($this->app->func->makeQuery($q)) {
                    $tmp['content']['status'] = '1';
                }
            }

            $items = $this->app->func->getItems($album);
            if (array_key_exists('sort', $data)) {
                foreach ($data['sort'] as $k => $v) {
                    if ($v === $items[$album][$k]['sort']) {
                        continue;
                    }

                    $q = "UPDATE " . TBL_ITEMS . " SET sort=" . $v . " WHERE item='" . $k . "' AND album='" . $album . "'";
                    if ($this->app->func->makeQuery($q)) {
                        $tmp['content']['status'] = '1';
                    }
                }
            }
        }

        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // load item
    public function loadItem()
    {
        $tmp['content']['status'] = '0';
        $tmp['content']['data'] = '';

        if ($this->app->sess->miuview_admin_in === true) {
            if ($items = $this->app->func->getItems($album)) {
                $arrnav = $this->app->func->array_navigate($items[$album], $item);
                $tmp['content']['prev'] = $items[$album][$arrnav['prev']]['item'];
                $tmp['content']['next'] = $items[$album][$arrnav['next']]['item'];

                if ($items[$album][$item]['type'] === 'picture') {
                    $html = PATH_TMPL . TEMPLATE . '/html/picture.html';
                }

                $a['item'] = $item;
                $a['next'] = $tmp['content']['next'];
                $a['thumb'] = URL . '?request=getimage&album=' . $album . '&item=' . $item . '&size=' . ITEM_SIZE . '&mode=longest&key=' . md5(SECURITY_KEY);
                $a['url'] = URL . '?request=getitem&album=' . $album . '&item=' . $item . '&size=' . ITEM_SIZE . '&thsize=' . TH_SIZE . '&key=';
                $a['title'] = $items[$album][$item]['title'];
                $a['description'] = $items[$album][$item]['description'];
                $a['width'] = $a['height'] = ITEM_SIZE;
                if (isset($html)) {
                    $tmp['content']['data'] .= $this->app->func->replace_tags($html, $a);
                }
            }
            $tmp['content']['status'] = '1';
        }
        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // submit item
    public function submitItem()
    {
        $tmp['content']['status'] = '0';

        if ($this->app->sess->miuview_admin_in === true) {
            $items = $this->app->func->getItems($album, $item);
            if (array_key_exists($album, $items) && array_key_exists($item, $items[$album])) {
                if ($items[$album][$item]['description'] !== $description || $items[$album][$item]['title'] !== $title) {
                    $q = "UPDATE " . TBL_ITEMS . " SET title='" . htmlentities($title, ENT_QUOTES) . "',description='" . htmlentities(
                            $description,
                            ENT_QUOTES
                        ) . "' WHERE album='" . $album . "' AND item='" . $item . "'";
                    if ($this->app->func->makeQuery($q)) {
                        $tmp['content']['status'] = '1';
                    }
                }
            }
        }

        $tmp['content_type'] = 'json';
        $this->output = $tmp;
    }

    // delete album
    public function deleteAlbum()
    {
        $tmp = array(
            'content' => array(
                'status' => 0
            ),
            'content_type' => 'json'
        );
        if (($this->app->sess->miuview_admin_in === true) && is_dir(PATH_ALBUMS . $album) && $this->app->func->removeDir(PATH_ALBUMS . $album)) {
            $tmp['content']['status'] = 1;
        }
        $this->output = $tmp;
    }

    // delete item
    public function deleteItem()
    {
        $tmp = array(
            'content' => array(
                'status' => 0
            ),
            'content_type' => 'json'
        );
        if ($this->app->sess->miuview_admin_in === true) {
            if (is_file(PATH_ALBUMS . $album . '/' . $item) && @unlink(PATH_ALBUMS . $album . '/' . $item)) {
                $tmp['content']['status'] = 1;
            }
        }
        $this->output = $tmp;
    }

    // upload
    public function upload()
    {
        if ($this->app->sess->miuview_admin_in !== true) {
            die('User must be logged in');
        }
        $tmp = array(
            'status' => 0
        );
        $uploadOk = 1;

        if (isset($album) && strpos($album, '../') === false && is_dir(PATH_ALBUMS . $album)) {
            $target_dir = PATH_ALBUMS . $album;
            $key = 'files2';
        } else {
            if (isset($_POST['hash']) && strpos($_POST['hash'], '../') === false) {
                if (!is_dir(PATH_ALBUMS . $_POST['hash']) && !mkdir($concurrentDirectory = PATH_ALBUMS . $_POST['hash']) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
                $target_dir = PATH_ALBUMS . $_POST['hash'];
                $key = 'files1';
            }
        }

        if (isset($target_dir)) {
            $target_file = $target_dir . '/' . basename($_FILES[$key]["name"][0]);
        }

        // check image
        $check = getimagesize($_FILES[$key]["tmp_name"][0]);
        if ($check === false) {
            $uploadOk = 0;
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            $target_file = $this->appendFileName($target_file);
        }
        // Check file size
        if ($_FILES[$key]["size"][0] > 10000000) {
            $uploadOk = 0;
        }

        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($imageFileType !== "jpg" && $imageFileType !== "png" && $imageFileType !== "jpeg" && $imageFileType !== "gif") {
            $uploadOk = 0;
        }

        if ($uploadOk === 1) {
            if (move_uploaded_file($_FILES[$key]["tmp_name"][0], $target_file)) {
                $tmp['status'] = $uploadOk;
            }
        }

        header('Content-Type: text/json');
        echo json_encode($tmp);
        exit;
    }

    private function appendFileName($name)
    {
        $path_parts = pathinfo($name);
        $actual_name = $path_parts['basename'];
        $original_name = $actual_name;
        $extension = $path_parts['extension'];
        $i = 1;
        while (file_exists($path_parts['dirname'] . '/' . $actual_name . "." . $extension)) {
            $actual_name = (string)$original_name . $i;
            $name = $path_parts['dirname'] . '/' . $actual_name . "." . $extension;
            $i++;
        }
        return $name;
    }

    // exif
    private function getExif($i)
    {
        require_once PATH_INC . 'exif.php';
        $e = new exif($i);
        if ($exif = $e->getExif()) {
            return json_encode($exif);
        } else {
            return false;
        }
    }

    // delete cache item
    private function deleteCacheItem($album, $item)
    {
        $path = PATH_CACHE . $album . '/';

        if (file_exists($path)) {
            // read content into array
            $caches = scandir($path);
            sort($caches);
            if (count($caches) > 2) { // The 2 accounts for . and ..
                // loop
                foreach ($caches as $cache) {
                    if ($cache !== '.' && $cache !== '..' && file_exists($path . $cache) && is_dir($path . $cache)) {
                        $files = scandir($path . $cache);
                        sort($files);
                        if (count($files) > 2) { // The 2 accounts for . and ..
                            if (file_exists($path . $cache . '/' . $item)) {
                                unlink($path . $cache . '/' . $item);
                            }
                        }
                    }
                }
            }
        }
    }

    // compare arrays, return mistakes
    private function compareArrays($array1, $array2, $rtype)
    {
        $tmp = $rtype === 'array' ? array(array(), array()) : array();
        foreach ($array1 as $ak => $av) {
            if (array_key_exists($ak, $array2)) {
                if ($u = array_diff($av, $array2[$ak])) {
                    if ($rtype === 'array') {
                        $tmp[1][$ak] = [];
                    } else {
                        $tmp[1] .= '<li class="folder"><b>' . $ak . '</b><ul>';
                    }
                    foreach ($u as $i) {
                        if ($rtype === 'array') {
                            $tmp[1][$ak][] = $i;
                        } else {
                            $tmp[1] .= '<li class="picture">' . $i . '</li>';
                        }
                    }
                    if ($rtype !== 'array') {
                        $tmp[1] .= '</ul></li>';
                    }
                }
            } elseif ($rtype === 'array') {
                $tmp[0][] = $ak;
            } else {
                $tmp[0] .= '<li class="folder">' . $ak . '</li>';
            }
        }
        return $tmp;
    }

    private function checkOrientation($path)
    {
        global $func;

        $data = [];
        if (file_exists($path)) {
            // read content into array
            $albums = scandir($path);
            sort($albums);
            if (count($albums) > 2) { /* The 2 accounts for . and .. */
                // loop
                foreach ($albums as $album) {
                    if (file_exists($path . $album) && $album !== '.' && $album !== '..' && is_dir($path . $album)) {
                        $items = scandir($path . $album);
                        sort($items);
                        if (count($items) > 2) { /* The 2 accounts for . and .. */
                            // loop
                            foreach ($items as $item) {
                                $type = $this->app->func->getType($path . $album . '/' . $item);
                                if (
                                    file_exists($path . $album . '/' . $item) &&
                                    $item !== '.' &&
                                    $item !== '..' &&
                                    !is_dir($path . $album . '/' . $item) &&
                                    $type &&
                                    $type['ext'] === 'jpg' &&
                                    function_exists('exif_read_data')
                                ) {
                                    $exif = exif_read_data($path . $album . '/' . $item);
                                    if ($exif && isset($exif['Orientation'])) {
                                        $orientation = $exif['Orientation'];
                                        if ($orientation !== 1) {
                                            $data[] = '<li class="picture">' . $path . $album . '/' . $item . '</li>';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return implode('', $data);
    }

    private function loadDB2array()
    {
        global $func;
        $tmp = [];

        $q = "SELECT album FROM " . TBL_ALBUMS . " ORDER BY album";
        if ($result = $this->app->func->makeQuery($q)) {
            while ($a = $result->fetch_assoc()) {
                $tmp[$a['album']] = [];
                $q = "SELECT item FROM " . TBL_ITEMS . " WHERE album = '" . $a['album'] . "' ORDER BY item";
                if ($result2 = $this->app->func->makeQuery($q)) {
                    while ($i = $result2->fetch_assoc()) {
                        $tmp[$a['album']][] = $i['item'];
                    }
                }
            }
        }
        return $tmp;
    }

    // read folder content into array
    private function folder2array($path)
    {
        global $func;
        $data = [];
        if (file_exists($path)) {
            // read content into array
            $albums = scandir($path);
            sort($albums);
            if (count($albums) > 2) { /* The 2 accounts for . and .. */
                // loop
                foreach ($albums as $album) {
                    if ($album !== '.' && $album !== '..' && file_exists($path . $album) && is_dir($path . $album)) {
                        $items = scandir($path . $album);
                        sort($items);
                        if (count($items) > 2) { /* The 2 accounts for . and .. */
                            // loop
                            foreach ($items as $item) {
                                if (file_exists($path . $album . '/' . $item) && $item != '.' && $item != '..' && !is_dir($path . $album . '/' . $item) && $this->app->func->getType(
                                        $path . $album . '/' . $item
                                    )) {
                                    $data[urlencode($album)][] = urlencode($item);
                                }
                            }
                        } else {
                            $data[urlencode($album)] = [];
                        }
                    }
                }
            }
        }
        return $data;
    }

    # read folder content into array, for cache
    private function folder2array2($path)
    {
        $data = [];
        if (file_exists($path)) {
            // read content into array
            $albums = scandir($path);
            sort($albums);
            if (count($albums) > 2) { /* The 2 accounts for . and .. */
                // loop albums
                foreach ($albums as $album) {
                    if (file_exists($path . $album) && $album != '.' && $album != '..' && is_dir($path . $album)) {
                        $caches = scandir($path . $album);
                        sort($caches);
                        if (count($caches) > 2) { /* The 2 accounts for . and .. */
                            // loop cache dimensions
                            foreach ($caches as $cache) {
                                if ($cache !== '.' && $cache !== '..' && file_exists($path . $album . '/' . $cache) && is_dir($path . $album . '/' . $cache)) {
                                    $items = scandir($path . $album . '/' . $cache);
                                    sort($items);
                                    if (count($items) > 2) { /* The 2 accounts for . and .. */
                                        // loop
                                        foreach ($items as $item) {
                                            if ($item !== '.' && $item !== '..' && file_exists($path . $album . '/' . $cache . '/' . $item) && !is_dir($path . $album . '/' . $cache . '/' . $item)) {
                                                $data[urlencode($album)][] = urlencode($item);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $data[urlencode($album)] = [];
                        }
                    }
                }
            }
        }
        return $data;
    }

    // format names
    private function formatNames($path)
    {
        if (file_exists($path)) {
            $files = scandir($path);
            if (count($files) > 2) {
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && file_exists($path . '/' . $file)) {
                        // rename
                        $nfile = preg_replace('/\s{2,}/', '', trim($file));
                        $nfile = str_replace(' ', '-', $nfile);
                        $nfile = preg_replace('/[^a-z0-9-._]/i', '', $nfile);
                        $nfile = preg_replace('/-{2,}/', '-', $nfile);
                        $nfile = strtolower($nfile);
                        if ($file !== $nfile) {
                            $nfile = $this->checkName($path, $nfile);
                        }
                        rename($path . '/' . $file, $path . '/' . $nfile);
                        //if dir
                        if (is_dir($path . '/' . $nfile)) {
                            $this->formatNames($path . '/' . $file);
                        }
                    }
                }
            }
        }
        return true;
    }

    private function checkName($path, $file)
    {
        if (file_exists($path . '/' . $file)) {
            if (is_dir($path . '/' . $file)) {
                $file .= '-1';
            } else {
                $file = substr_replace($file, '-1.', strrpos($file, '.'), 1);
            }
            return $this->checkName($path, $file);
        } else {
            return $file;
        }
    }

    // return output
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param $type
     * @param int $akey
     * @param mixed $item
     * @return void
     */
    private function insertItem($type, int $akey, mixed $item): void
    {
        $meta = $this->getExif("{$this->app->conf['PATH_ALBUMS']}/$akey/$item") ?: '';
        $q = "INSERT INTO {$this->app->conf['TBL_ITEMS']} (item,type,album,description,metadata,sort,added) " .
            "SELECT '$item', '$type', '$akey', '', '$meta', " .
            "CASE WHEN (SELECT COUNT(item) FROM {$this->app->conf['TBL_ITEMS']}) = 0 THEN 0 " .
            "ELSE (SELECT MAX(sort) + 1 FROM {$this->app->conf['TBL_ITEMS']}) END, NOW()";
        $this->app->func->makeQuery($q);
    }
}
