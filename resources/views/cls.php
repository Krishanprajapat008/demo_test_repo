<?php
include_once ("connection.php");
include_once ("image_magician/php_image_magician.php");
include_once ("dbclass.php");
include_once ("PHPMailer/PHPMailerAutoload.php");

//include 'resize/ImageResize.php';

ini_set('memory_limit', '-1');

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

global $dbh;

class hcp extends dbclass
{

    function getmaterialchild($table, $parent, $level, $m_parent, $uptolevel = -1, $pageData = array())
    {
        global $dbh;
        $c_q  = "select p.id,p.parent_id,p.page from " . $table . " p
                where p.parent_id ='" . $parent . "'  order by p.position";
        $stmt = $this->dbh->prepare($c_q);
        $stmt->execute();

        $level = $level + 1;
        while ($c_r   = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $disabled = '';
            if (isset($pageData['id']) && $pageData['id'] > 0)
            {
                if ($pageData['id'] == $c_r['id'])
                {
                    $disabled = 'disabled';
                }
            }
            ?>
            <option <?= $disabled; ?> value="<?= $c_r['id']; ?>" <?php
            if (@$m_parent == $c_r['id'])
            {
                ?>
                                          selected="selected"
                                      <?php } ?>>--
                                          <?= $c_r['page'] ?>
            </option>
            <?php
            if ($uptolevel != -1 && $level == $uptolevel)
            {
                continue;
            }
            $this->getmaterialchild('pages', $c_r['id'], $levael, $m_parent);
        }
    }

    function getmaterialchildBan($table, $parent, $level, $m_parent, $uptolevel = -1)
    {
        global $dbh;
        $c_q  = "select p.id,p.parent_id,p.page from " . $table . " p
                where p.parent_id ='" . $parent . "' and banner = '1'  order by p.position";
        $stmt = $this->dbh->prepare($c_q);
        $stmt->execute();

        $level = $level + 1;
        while ($c_r   = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            ?>
            <option value="<?= $c_r['id'] ?>" <?php
            if (@$m_parent == $c_r['id'])
            {
                ?>
                        selected="selected"
                    <?php } ?>>--
                        <?= $c_r['page'] ?>
            </option>
            <?php
            if ($uptolevel != -1 && $level == $uptolevel)
            {
                continue;
            }
            $this->getmaterialchild('pages', $c_r['id'], $levael, $m_parent);
        }
    }

    function getmaterialchildcat($table, $parent, $level, $m_parent, $uptolevel = -1)
    {
        global $dbh;
        $c_q  = "select p.id,p.parent_id,p.title from " . $table . " p
                where p.parent_id ='" . $parent . "'  order by p.position";
        $stmt = $this->dbh->prepare($c_q);
        $stmt->execute();

        $level = $level + 1;
        while ($c_r   = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            ?>
            <option value="<?= $c_r['id'] ?>" <?php
            if (@$m_parent == $c_r['id'])
            {
                ?>
                        selected="selected"
                    <?php } ?>>--
                        <?= $c_r['title'] ?>
            </option>
            <?php
            if ($uptolevel != -1 && $level == $uptolevel)
            {
                continue;
            }
            $this->getmaterialchildcat('categories', $c_r['id'], $levael, $m_parent);
        }
    }

    function getMenu()
    {
        global $dbh;
        $qry        = $this->dbh->prepare("SELECT p.* from pages p where p.parent_id = 0 order by position ASC");
        $qry->execute();
        $menu_items = $qry->fetchAll(PDO::FETCH_ASSOC);
        $str        = "";
        ?>
        <ol class="dd-list">
            <?php
            foreach ($menu_items as $menu)
            {
                if (!$this->checkHasChild($menu['id']))
                {
                    $this->drawSingle($menu);
                }
                else
                {
                    $this->drawMultiple($menu);
                }
            }
            ?>
        </ol>
        <?php
    }

    function getMenuCat()
    {
        global $dbh;
        $qry        = $this->dbh->prepare("SELECT c.* from categories c where c.parent_id = 0 order by position ASC");
        $qry->execute();
        $menu_items = $qry->fetchAll(PDO::FETCH_ASSOC);
        $str        = "";
        ?>
        <ol class="dd-list">
            <?php
            foreach ($menu_items as $menu)
            {
                if (!$this->checkHasChildCat($menu['id']))
                {
                    $this->drawSingleCat($menu);
                }
                else
                {
                    $this->drawMultipleCat($menu);
                }
            }
            ?>
        </ol>
        <?php
    }

    function createGallery($type, $ref_id, $json_data)
    {
        $new_gallery = 1;
        $conds_check = array('gallery_type' => $type, 'project_id' => $ref_id);
        $check_data  = $this->getTable('gallery', $conds_check, true);
        if (isset($check_data['id']) && $check_data['id'] > 0)
        {
            $new_gallery = 0;
            $gallery_id  = $check_data['id'];
        }

        if ($new_gallery == 1)
        {
            $gallery_tabledata = array(
                "d_table_name" => 'gallery'
            );
            $gallery_data      = array(
                'gallery_type' => $type,
                'project_id'   => $ref_id,
                'add_date'     => date('Y-m-d H:i:s')
            );

            $this->addEditTable($gallery_data, $gallery_tabledata);
            $gallery_id = $this->last_insert_id;
        }

        $gallery_files = json_decode($json_data, true);

        foreach ($gallery_files as $files)
        {
            $file_name = $files['serverFileName'];
            if ($type == 'P1' || $type == 'p1')
            {
                $check_type = 'product_images';
            }
            if ($type == 'P2' || $type == 'p2')
            {
                $check_type = 'project_images';
            }

            $position           = $this->getLastPosition('gallery_images', 'id', 'gallery_id', $gallery_id);
            $gallery_imagetable = array(
                "d_table_name" => 'gallery_images'
            );
            $image_data         = array(
                'gallery_id' => $gallery_id,
                'image_path' => str_replace('"', '', $file_name),
                'position'   => $position,
                'add_date'   => date('Y-m-d H:i:s')
            );

            $this->addEditTable($image_data, $gallery_imagetable);
        }
    }

    function upload_images($files, $type = 'project', $return_value = false)
    {

        $allowedExts = array("gif", "jpeg", "jpg", "png", "GIF", "JPEG", "JPG", "PNG");

        $desk_size  = 1280;
        //$tab_size   = 600;
        $thumb_size = 750;

        if ($type == 'product')
        {
            $imagePath = '../../resources/admin_uploads/products/gallery/';
            $fl        = $files['files'];
        }
        else if ($type == 'project')
        {
            $imagePath = '../../resources/admin_uploads/projects/gallery/';
            $fl        = $files['files'];
        }

        if (isset($fl))
        {

            $temp      = explode(".", $fl["name"][0]);
            $extension = end($temp);
            if (!is_writable($imagePath))
            {
                $response         = array();
                $response['type'] = 'error';
                $response['msg']  = 'error in upload';
                print json_encode($response);
                return;
            }
            if (in_array($extension, $allowedExts))
            {
                if ($fl["error"][0] > 0)
                {
                    $response         = array();
                    $response['type'] = 'error';
                    $response['msg']  = 'invalid file';
                }
                else
                {
                    $new_file             = array();
                    $new_file['tmp_name'] = $fl["tmp_name"][0];
                    $new_file['name']     = $fl["name"][0];
                    $new_file['type']     = $fl["type"][0];
                    $new_file['error']    = $fl["error"][0];
                    $new_file['size']     = $fl["size"][0];
                    $filename             = $fl["tmp_name"][0];
                    $new_file_name        = $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                    // $new_file_name_mobile = 'mobile/' . $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                    $new_file_name_desk   = $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                    $new_file_name_thumb  = 'thumbs/' . $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                    move_uploaded_file($filename, $imagePath . $new_file_name);

                    $info = getimagesize($imagePath . $new_file_name);

                    $desk_quality   = 90;
                    $mobile_quality = 80;

                    $filesizeInfo = filesize($imagePath . $new_file_name);

                    if ($filesizeInfo <= 100000)
                    {
                        $desk_quality   = 100;
                        $mobile_quality = 95;
                    }
                    else if ($filesizeInfo <= 1000000)
                    {
                        $desk_quality   = 90;
                        $mobile_quality = 85;
                    }
                    else if ($filesizeInfo <= 4000000)
                    {
                        $desk_quality   = 85;
                        $mobile_quality = 75;
                    }
                    else if ($filesizeInfo > 4000000)
                    {
                        $response         = array();
                        $response['type'] = 'error';
                        $response['msg']  = 'error in upload, image too large';
                        unlink($imagePath . $new_file_name);
                        echo json_encode($response);
                        exit;
                    }

                    $desk_quality = 100;

                    //$this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_mobile, $tab_size, 0, $mobile_quality);
                    $this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_desk, $desk_size, 768, $desk_quality, 0);
                    $this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_thumb, $thumb_size, 450, $mobile_quality, 0);

                    $response = $new_file_name;
                }
            }
            else
            {
                $response         = array();
                $response['type'] = 'error';
                $response['msg']  = 'filetype not allowed';
            }
        }
        else
        {
            $response         = array();
            $response['type'] = 'error';
            $response['msg']  = 'error in upload, image too large';
        }
        if ($return_value)
        {
            return $response;
        }
        else
        {
            echo json_encode($response);
        }
    }

    function upload_project_image($files, $type = 'project', $return_value = false, $research = 0)
    {

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $allowedExts = array("gif", "jpeg", "jpg", "png", "GIF", "JPEG", "JPG", "PNG");

        $desk_size  = 1200;
        $tab_size   = 600;
        $thumb_size = 400;
        if ($research == 1)
        {
            $desk_size = 720;
            $tab_size  = 400;
        }

        if ($type == 'project')
        {
            $imagePath = '../../assets/projects/images/';
            $fl        = $files['files'];
        }
        if ($type == 'cases')
        {
            $imagePath = '../../assets/cases/images/';
            $fl        = $files['files'];
        }
        else if ($type == 'drawing')
        {
            $imagePath = '../../assets/projects/drawings/';
            $fl        = $files['files1'];
        }
        else if ($type == 'main')
        {
            $imagePath = '../assets/projects/';
            $fl        = $files;
        }


        if (isset($fl))
        {
            $temp      = explode(".", $fl["name"][0]);
            $extension = end($temp);
            if (!is_writable($imagePath))
            {
                $response         = array();
                $response['type'] = 'error';
                $response['msg']  = 'error in upload';
                print json_encode($response);
                return;
            }
            if (in_array($extension, $allowedExts))
            {
                if ($fl["error"][0] > 0)
                {
                    $response         = array();
                    $response['type'] = 'error';
                    $response['msg']  = 'invalid file';
                }
                else
                {
                    $new_file             = array();
                    $new_file['tmp_name'] = $fl["tmp_name"][0];
                    $new_file['name']     = $fl["name"][0];
                    $new_file['type']     = $fl["type"][0];
                    $new_file['error']    = $fl["error"][0];
                    $new_file['size']     = $fl["size"][0];
                    $filename             = $fl["tmp_name"][0];
                    $new_file_name        = $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
//                            $new_file_name_mobile = 'mobile/' . $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                    $new_file_name_thumb  = 'thumbs/' . $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                    move_uploaded_file($filename, $imagePath . $new_file_name);

                    $info = getimagesize($imagePath . $new_file_name);
                    if ($research != 1)
                    {
                        $desk_size = $info[0];
                    }

                    $desk_quality   = 90;
                    $mobile_quality = 80;

                    $filesizeInfo = filesize($imagePath . $new_file_name);

                    if ($filesizeInfo <= 100000)
                    {
                        $desk_quality   = 100;
                        $mobile_quality = 95;
                    }
                    else if ($filesizeInfo <= 1000000)
                    {
                        $desk_quality   = 90;
                        $mobile_quality = 85;
                    }
                    else if ($filesizeInfo <= 4000000)
                    {
                        $desk_quality   = 85;
                        $mobile_quality = 75;
                    }
                    else if ($filesizeInfo > 4000000)
                    {
                        $response         = array();
                        $response['type'] = 'error';
                        $response['msg']  = 'error in upload, image too large';
                        unlink($imagePath . $new_file_name);
                        echo json_encode($response);
                        exit;
                    }

                    $desk_quality = 100;

//                    if ($info[2] == IMAGETYPE_PNG)
//                    {
//                        $output               = "hcp_" . date('ymdhis') . '.jpg';
//                        $this->png2jpg($imagePath . $new_file_name, $output, 100);
//                        $new_file_name        = $type . '_' . date('YmdHis') . '_' . $output;
//                        $new_file_name_mobile = 'mobile/' . $type . '_' . date('YmdHis') . '_' . $output;
//                        $new_file_name_thumb  = 'thumbs/' . $type . '_' . date('YmdHis') . '_' . $output;
//                        $this->make_thumb($output, $imagePath . $new_file_name, $desk_size, 0, $desk_quality);
//                        $this->make_thumb($output, $imagePath . $new_file_name_mobile, $tab_size, 0, $mobile_quality);
//                        $this->make_thumb($output, $imagePath . $new_file_name_thumb, $thumb_size, 0, $mobile_quality);
//                        unlink($output);
//                    }
//                    else
//                    {
                    //$this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name, $desk_size, 0, $desk_quality);
                    if ($research == 1)
                    {
                        ///Added by Nishant Solanki, to resize exibition images, as per asked by Sandip bhai on 13th october 2017 (resize images by height)
                        $this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name, 0, $desk_size, $desk_quality, 1);
                        // $this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_mobile, 0, $tab_size, $mobile_quality, 1);
                    }
                    else
                    {
                        // $this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_mobile, $tab_size, 0, $mobile_quality);
                        $this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_thumb, $thumb_size, 0, $mobile_quality);
                    }
                    //}

                    $response = $new_file_name;
                }
            }
            else
            {
                $response         = array();
                $response['type'] = 'error';
                $response['msg']  = 'filetype not allowed';
            }
        }
        else
        {
            $response         = array();
            $response['type'] = 'error';
            $response['msg']  = 'error in upload, image too large';
        }
        if ($return_value)
        {
            return $response;
        }
        else
        {
            echo json_encode($response);
        }
    }

    function upload_csv($data, $files)
    {


        $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');

        // If the file is uploaded
        // Open uploaded CSV file with read-only mode
        $csvFile = fopen($_FILES['doc_path_upload']['tmp_name'], 'r');
        // Skip the first line
        fgetcsv($csvFile);

        // Parse data from CSV file line by line
        while (($line = fgetcsv($csvFile)) !== FALSE)
        {
            // Get row data
//                            var_dump($line);exit;
            $id            = $line[0];
            $submission_id = $line[1];
            $acceptance    = $line[2];
            $title         = $line[3];
            $full_name     = $line[4];

            // Check whether member already exists in the database with the same email
//                            $prevQuery = "SELECT id FROM members WHERE email = '".$line[1]."'";
//                            $prevResult = $db->query($prevQuery);
            // Insert member data in the database

            $sql = "INSERT INTO author_data2 (id, submission_id, acceptance, title, full_name) VALUES ('" . $id . "', '" . $submission_id . "', '" . $acceptance . "', '" . $title . "', '" . $full_name . "')";
            $log = $this->dbh->prepare($sql);
            $log->execute();
        }

        // Close opened CSV file
        fclose($csvFile);
    }
    
    function get_faculty_staff_applicant_records($table, $id)
    {
//        $sql      = "Select `resume_upload`,`teaching_statement`,`research_statement`,`published_papers` FROM $table where id = $id ";
        $sql      = "Select * FROM $table where id = $id ";
        $qry      = $this->dbh->prepare($sql);
        $qry->execute();
        return $pageData = $qry->fetch(PDO::FETCH_ASSOC);
        
    }
    
    function get_attachments($table, $faculty_id, $car_id)
    {
        $sql      = "Select * FROM $table where career_id = $car_id and id IN ($faculty_id) ";
        $qry      = $this->dbh->prepare($sql);
        $qry->execute();
        return $pageData = $qry->fetchAll(PDO::FETCH_ASSOC); 
    }

    function upload_images1($files, $type = 'project', $return_value = false)
    {

        $allowedExts = array("gif", "jpeg", "jpg", "png", "GIF", "JPEG", "JPG", "PNG");

        $desk_size  = 1280;
        //$tab_size   = 600;
        $thumb_size = 750;

        if ($type == 'product')
        {
            $imagePath = '../../resources/admin_uploads/products/gallery/';
            $fl        = $files;
        }
        else if ($type == 'project')
        {
            $imagePath = '../../resources/admin_uploads/projects/gallery/';
            $fl        = $files;
        }

        $temp      = explode(".", $fl["name"]);
        $extension = end($temp);
        if (!is_writable($imagePath))
        {
            $response         = array();
            $response['type'] = 'error';
            $response['msg']  = 'error in upload';
            print json_encode($response);
            return;
        }
        if (in_array($extension, $allowedExts))
        {
            if ($fl["error"] > 0)
            {
                $response         = array();
                $response['type'] = 'error';
                $response['msg']  = 'invalid file';
            }
            else
            {

                $new_file             = array();
                $new_file['tmp_name'] = $fl["tmp_name"];
                $new_file['name']     = $fl["name"];
                $new_file['type']     = $fl["type"];
                $new_file['error']    = $fl["error"];
                $new_file['size']     = $fl["size"];

                $filename            = $fl["tmp_name"];
                $new_file_name       = $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'])));
                // $new_file_name_mobile = 'mobile/' . $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'][0])));
                $new_file_name_desk  = $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'])));
                $new_file_name_thumb = 'thumbs/' . $type . '_' . date('YmdHis') . '_' . strtolower(preg_replace('/[^A-Za-z0-9\.]/', "_", basename($fl['name'])));

                move_uploaded_file($filename, $imagePath . $new_file_name);

                $info = getimagesize($imagePath . $new_file_name);

                $desk_quality   = 90;
                $mobile_quality = 80;

                $filesizeInfo = filesize($imagePath . $new_file_name);

                if ($filesizeInfo <= 100000)
                {
                    $desk_quality   = 100;
                    $mobile_quality = 95;
                }
                else if ($filesizeInfo <= 1000000)
                {
                    $desk_quality   = 90;
                    $mobile_quality = 85;
                }
                else if ($filesizeInfo <= 4000000)
                {
                    $desk_quality   = 85;
                    $mobile_quality = 75;
                }
                else if ($filesizeInfo > 4000000)
                {
                    $response         = array();
                    $response['type'] = 'error';
                    $response['msg']  = 'error in upload, image too large';
                    unlink($imagePath . $new_file_name);
                    echo json_encode($response);
                    exit;
                }

                $desk_quality = 100;

                //$this->make_thumb($imagePath . $new_file_name, $imagePath . $new_file_name_mobile, $tab_size, 0, $mobile_quality);

                $this->make_thumb1($imagePath . $new_file_name, $imagePath . $new_file_name_desk, $desk_size, 768, $desk_quality, 0);

                $this->make_thumb1($imagePath . $new_file_name, $imagePath . $new_file_name_thumb, $thumb_size, 450, $mobile_quality, 0);

                $response = $new_file_name;
            }
        }
        else
        {
            $response         = array();
            $response['type'] = 'error';
            $response['msg']  = 'filetype not allowed';
        }

        if ($return_value)
        {
            return $response;
        }
        else
        {
            echo json_encode($response);
        }
    }

    function compress_image($source_url, $destination_url, $quality)
    {
        $info = getimagesize($source_url);
        if ($info['mime'] == 'image/jpeg')
        {
            $image = imagecreatefromjpeg($source_url);
            imagejpeg($image, $destination_url, $quality);
        }
        else if ($info['mime'] == 'image/gif')
        {
            $image = imagecreatefromgif($source_url);
            imagejpeg($image, $destination_url, $quality);
        }
        else if ($info['mime'] == 'image/png')
        {
            $image = imagecreatefrompng($source_url);
            imagesavealpha($image, true);
            imagepng($image, $destination_url, 5);
        }

        return $destination_url;
    }

    function runOrdering2($data, $flag = 'p')
    {
        $position = 1;
        if (is_array($data))
        {
            foreach ($data as $parent)
            {
                if ($flag == 'p')
                {
                    $this->changeOrder($parent['id'], 0, $position);
                }
                if (isset($parent['children']))
                {
                    $child_position = 1;
                    foreach ($parent['children'] as $child)
                    {
                        $this->changeOrder($child['id'], $parent['id'], $child_position);
                        $child_position++;
                        if (isset($child['children']))
                        {
                            $arr    = array();
                            $arr[0] = $child;
                            $this->runOrdering2($arr, 'c');
                        }
                    }
                }
                $position++;
            }
        }
    }

    function runOrdering2cat($data, $flag = 'p')
    {
        $position = 1;
        if (is_array($data))
        {
            foreach ($data as $parent)
            {
                if ($flag == 'p')
                {
                    $this->changeOrderCat($parent['id'], 0, $position);
                }
                if (isset($parent['children']))
                {
                    $child_position = 1;
                    foreach ($parent['children'] as $child)
                    {
                        $this->changeOrderCat($child['id'], $parent['id'], $child_position);
                        $child_position++;
                        if (isset($child['children']))
                        {
                            $arr    = array();
                            $arr[0] = $child;
                            $this->runOrdering2cat($arr, 'c');
                        }
                    }
                }
                $position++;
            }
        }
    }

    function checkHasChild($id)
    {
        global $dbh;
        $qry = $this->dbh->prepare("select p.* from pages p where p.parent_id = " . $id . " order by position ASC");
        $qry->execute();
        if ($qry->rowCount() > 0)
        {
            return $child_items = $qry->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }

    function checkHasChildCat($id)
    {
        global $dbh;
        $qry = $this->dbh->prepare("select c.* from categories c where c.parent_id = " . $id . " order by position ASC");
        $qry->execute();
        if ($qry->rowCount() > 0)
        {
            return $child_items = $qry->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }

    function drawSingle($data)
    {
        ?>
        <li class="dd-item" data-id="<?php echo $data['id'] ?>">
            <div class="dd-handle">
                <span class="pageHandle">
                    <?php
                    echo ucwords($data['page']);
                    ?>
                </span>
                <div class="nestable_actions dd-nodrag">
                    <?php
                    if ($data['status'] == '0')
                    {
                        ?>
                        <a  href="javascript:void(0)"><i title="Inactive" onclick='return changeStatus("careers_pages", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "pages.php");' class=' fa-minus-square fa pointer'></i></a>
                        <?php
                    }
                    else if ($data['status'] == '1')
                    {
                        ?>
                        <a  href="javascript:void(0)"><i title="Active" onclick='return changeStatus("careers_pages", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "pages.php");'  class='fa-check-square-o fa pointer'></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;
                    <a href="add_edit_page.php?page_id=<?php echo $data['id'] ?>&amp;edit=1" title="Edit"><i class="fa fa-edit pointer"></i></a>&nbsp;&nbsp;&nbsp;
                    <?php
                    if ($data['not_delete'] == 0)
                    {
                        ?>
                        <a onclick="AjaxDelete2('careers_pages', 'id',<?php echo $data['id'] ?>, 'data_grid', 'page', 'ASC', '30', '', '1', 'pages.php');" href="javascript:void(0)" title="Delete"><i class="fa fa-remove  pointer"></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a data-link="<?= ROOT_URL . $data['slug']; ?>" href="javascript:void(0)" class="get_link" title="Edit"><i class="fa fa-link pointer"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                </div>
            </div>
        </li>
        <?php
    }

    function drawMultiple($data)
    {
        ?>
        <li class="dd-item" data-id="<?php echo $data['id'] ?>">
            <div class="dd-handle">
                <span class="pageHandle">
                    <?php
                    echo ucwords($data['page']);
                    ?>
                </span>
                <div class="nestable_actions dd-nodrag">
                    <?php
                    if ($data['status'] == '0')
                    {
                        ?>
                        <a href="javascript:void(0)"><i title="Inactive" onclick='return changeStatus("careers_pages", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "pages.php");' class=' fa-minus-square fa pointer'></i></a>
                        <?php
                    }
                    else if ($data['status'] == '1')
                    {
                        ?>
                        <a href="javascript:void(0)"><i title="Active" onclick='return changeStatus("careers_pages", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "pages.php");'  class='fa-check-square-o fa pointer'></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;
                    <a href="add_edit_page.php?page_id=<?php echo $data['id'] ?>&amp;edit=1" title="Edit"><i class="fa fa-edit pointer"></i></a>&nbsp;&nbsp;&nbsp;
                    <?php
                    if ($data['not_delete'] == 0)
                    {
                        ?>
                        <a onclick="AjaxDelete2('careers_pages', 'id',<?php echo $data['id'] ?>, 'data_grid', 'page', 'ASC', '30', '', '1', 'pages.php');" href="javascript:void(0)" title="Delete"><i class="fa fa-remove  pointer"></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a data-link="<?= ROOT_URL . $data['slug']; ?>" href="javascript:void(0)" class="get_link" title="Edit"><i class="fa fa-link pointer"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                </div>
            </div>
            <ol class="dd-list">
                <?php
                foreach ($this->checkHasChild($data['id']) as $child_data)
                {
                    if (!$this->checkHasChild($child_data['id']))
                    {
                        $this->drawSingle($child_data);
                    }
                    else
                    {
                        $this->drawMultiple($child_data);
                    }
                }
                ?>
            </ol>
        </li>
        <?php
    }

    function drawSingleCat($data)
    {
        ?>
        <li class="dd-item" data-id="<?php echo $data['id'] ?>">
            <div class="dd-handle">
                <span class="pageHandle">
                    <?php
                    echo ucwords($data['title']);
                    ?>
                </span>
                <div class="nestable_actions dd-nodrag">
                    <?php
                    if ($data['status'] == '0')
                    {
                        ?>
                        <a  href="javascript:void(0)"><i title="Inactive" onclick='return changeStatus("categories", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "categories.php");' class='fas fa-minus-square pointer'></i></a>
                        <?php
                    }
                    else if ($data['status'] == '1')
                    {
                        ?>
                        <a  href="javascript:void(0)"><i title="Active" onclick='return changeStatus("categories", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "categories.php");'  class='far fa-check-square pointer'></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;
                    <a href="add_edit_category.php?cat_id=<?php echo $data['id'] ?>&amp;edit=1" title="Edit"><i class="far fa-edit pointer"></i></a>&nbsp;&nbsp;&nbsp;
                    <?php
                    if ($data['not_delete'] == 0)
                    {
                        ?>
                        <a onclick="AjaxDelete2('categories', 'id',<?php echo $data['id'] ?>, 'data_grid', 'category', 'ASC', '30', '', '1', 'categories.php');" href="javascript:void(0)" title="Delete"><i class="fas fa-times pointer"></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a data-link="<?= ROOT_URL . $data['slug']; ?>" href="javascript:void(0)" class="get_link" title="Edit"><i class="fa fa-link pointer"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                </div>
            </div>
        </li>
        <?php
    }

    function drawMultipleCat($data)
    {
        ?>
        <li class="dd-item" data-id="<?php echo $data['id'] ?>">
            <div class="dd-handle">
                <span class="pageHandle">
                    <?php
                    echo ucwords($data['title']);
                    ?>
                </span>
                <div class="nestable_actions dd-nodrag">
                    <?php
                    if ($data['status'] == '0')
                    {
                        ?>
                        <a href="javascript:void(0)"><i title="Inactive" onclick='return changeStatus("categories", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "categories.php");' class='fas fa-minus-square pointer'></i></a>
                        <?php
                    }
                    else if ($data['status'] == '1')
                    {
                        ?>
                        <a href="javascript:void(0)"><i title="Active" onclick='return changeStatus("categories", "id",<?= $data['id'] ?>, "nestable", "ASC", "position", "30", "", "0", "categories.php");'  class='far fa-check-square pointer'></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;
                    <a href="add_edit_category.php?cat_id=<?php echo $data['id'] ?>&amp;edit=1" title="Edit"><i class="far fa-edit pointer"></i></a>&nbsp;&nbsp;&nbsp;
                    <?php
                    if ($data['not_delete'] == 0)
                    {
                        ?>
                        <a onclick="AjaxDelete2('categories', 'id',<?php echo $data['id'] ?>, 'data_grid', 'page', 'ASC', '30', '', '1', 'categories.php');" href="javascript:void(0)" title="Delete"><i class="fas fa-times  pointer"></i></a>
                        <?php
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a data-link="<?= ROOT_URL . $data['slug']; ?>" href="javascript:void(0)" class="get_link" title="Edit"><i class="fa fa-link pointer"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                </div>
            </div>
            <ol class="dd-list">
                <?php
                foreach ($this->checkHasChildCat($data['id']) as $child_data)
                {
                    if (!$this->checkHasChildCat($child_data['id']))
                    {
                        $this->drawSingleCat($child_data);
                    }
                    else
                    {
                        $this->drawMultipleCat($child_data);
                    }
                }
                ?>
            </ol>
        </li>
        <?php
    }

    function Login($user_name, $password)
    {
        $res        = array();
        $checkEmail = $this->dbh->prepare("select user_name from `careers_admin` where user_name = :user_name");
        $checkEmail->execute(array(':user_name' => $user_name));
        $data       = $checkEmail->fetch(PDO::FETCH_ASSOC);
        if ($checkEmail->rowCount() > 0)
        {
            $selectUser = $this->dbh->prepare("select * from `careers_admin` where user_name = :user_name and password = :password");
            $password   = md5($password . PWD_STRING);
            $selectUser->execute(array(':user_name' => $user_name, ':password' => $password));
            $data1      = $selectUser->fetch(PDO::FETCH_ASSOC);
            if ($selectUser->rowCount() > 0)
            {
                if (!$this->checkUserStatus($user_name, $password))
                {
                    $res['msg']    = "ERROR : Your account hasn't been verified yet";
                    $res['result'] = 'ERROR';
                }
                else
                {
                    $this->InsertTimestamp('careers_admin', 'last_login', $data1['id'], 'id');
                    $res['msg']    = $data1;
                    $res['result'] = 'SUCCESS';
                }
            }
            else
            {
                $res = $this->CreateMSG('wrong', 'password', 'ERROR');
            }
        }
        else
        {
            $res = $this->CreateMSG('Invalid', 'user name', 'ERROR');
        }
        return $res;
    }

    function ResetPassword($user_name, $password)
    {
        $password   = md5($password . PWD_STRING);
        $checkEmail = $this->dbh->prepare("UPDATE careers_admin set password = :password where email_id = :user_name");
        $checkEmail->execute(array(':password' => $password, ':user_name' => $user_name));
    }

    function ForgotPassword($user_name)
    {
        $res        = array();
        $checkEmail = $this->dbh->prepare("select * from careers_admin where email_id = :user_name");
        $checkEmail->execute(array(':user_name' => $user_name));
        $data       = $checkEmail->fetch(PDO::FETCH_ASSOC);

        /* if ($checkEmail->rowCount() > 0)
          {
          //$link = ROOT_URL . 'admin/reset_password.php?e=' . $this->encrypt($user_name);

          $link = "link";
          $to      = $data['email_id'];
          $subject = "SNU: Update Password";
          $txt     = EMAILHEADER . "Hello,<br/><br/>
          Please click below link to reset your password,<br/>
          " . $link . "<br/>
          <br/><br/>Thank you." . EMAILFOOTER;

          //                $headers = "MIME-Version: 1.0" . "\r\n";
          //                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
          //                $headers .= 'From:  <>' . "\r\n";
          //                mail($to, $subject, $txt, $headers);

          $this->send_email($to, $subject, $link);
          $res     = $this->CreateMSG('Invalid', 'email', 'SUCCESS');
          } */


        if ($checkEmail->rowCount() > 0)
        {
            //$link = ROOT_URL . 'admin/reset_password.php?e=' . $this->encrypt($user_name);

            $link    = "link123";
            $to      = $data['email_id'];
            $subject = "snu: Update Password";
            $txt     = EMAILHEADER . "Hello,<br/><br/>
                            Please click below link to reset your password,<br/>
                            " . $link . "<br/>
                            <br/><br/>Thank you." . EMAILFOOTER;
            //$headers = "MIME-Version: 1.0" . "\r\n";
            //$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            //$headers .= 'From: Ahmedabad University <info@ahduni.edu.in>' . "\r\n";
            //mail($to, $subject, $txt, $headers);
            $this->send_email($to, $subject, $txt);
            $res     = $this->CreateMSG('Invalid', 'email', 'SUCCESS');
        }
        else
        {
            $res = $this->CreateMSG('Invalid', 'email', 'ERROR');
        }
        return $res;
    }

    function get_slug($id, $table)
    {
        $check_slug = $this->dbh->prepare("select slug from `$table` where id = :slug_id");
        $check_slug->execute(array(':slug_id' => $id));
        $data       = $check_slug->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    function encrypt($pure_string)
    {
        $dirty            = array("+", "/", "=");
        $clean            = array("_PLUS_", "_SLASH_", "_EQUALS_");
        $iv_size          = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $_SESSION['iv']   = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, PWD_STRING, utf8_encode($pure_string), MCRYPT_MODE_ECB, $_SESSION['iv']);
        $encrypted_string = base64_encode($encrypted_string);
        return str_replace($dirty, $clean, $encrypted_string);
    }

    function decrypt($encrypted_string)
    {
        $dirty = array("+", "/", "=");
        $clean = array("_PLUS_", "_SLASH_", "_EQUALS_");

        $string = base64_decode(str_replace($clean, $dirty, $encrypted_string));

        $decrypted_string = @mcrypt_decrypt(MCRYPT_BLOWFISH, PWD_STRING, $string, MCRYPT_MODE_ECB, $_SESSION['iv']);
        return $decrypted_string;
    }

    function InsertTimestamp($table, $field, $id, $id_field)
    {
        $insertTimestamp = "update $table set $field = '" . date('Y-m-d H:i:s') . "' where $id_field = $id";
        $qry             = $this->dbh->prepare($insertTimestamp);
        $qry->execute();
    }

    function checkUserStatus($user_name, $password)
    {
        $res         = array();
        $checkStatus = $this->dbh->prepare("select status from careers_admin where user_name = :user_name and password = :password");
        $checkStatus->execute(array(':user_name' => $user_name, ':password' => $password));
        $data        = $checkStatus->fetch(PDO::FETCH_ASSOC);
        if ($data['status'] == 1)
        {
            return true;
        }
        else if ($data['status'] == 0)
        {
            return false;
        }
    }

    function ValidateEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function changeOrder($id, $parent_id, $position)
    {
        global $dbh;
        if (isset($id))
        {
            $qry = $this->dbh->prepare("update pages set parent_id = $parent_id, position = $position where id =" . $id);
            $qry->execute();
        }
        else
        {
            //echo $parent_id;
        }
    }

    function get_total_entries($career_id, $type)
    {

        if ($type == 'S')
        {
            $table = 'careers_staff_form';
        }
        if ($type == 'F')
        {
            $table = 'careers_faculty_form';
        }

        $data       = "Select * from " . $table . " where career_id = " . $career_id;
        $qry1       = $this->dbh->prepare($data);
        $qry1->execute();
        $page_info1 = $qry1->fetchAll(PDO::FETCH_ASSOC);

//            $cnt = count($page_info1);
//            return $arr = array('type' => $type, 'total_entries' => $cnt);

        return count($page_info1);
    }

    function get_yesterdays_received_applications($table = '')
    {
        if ($table != '')
        {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $qry       = $this->dbh->prepare("select * from " . $table . " where add_date >= CURDATE() - INTERVAL 1 DAY order by id ASC");
            $qry->execute();
            if ($qry->rowCount() > 0)
            {
                $total_count = $qry->fetchAll(PDO::FETCH_ASSOC);
                return count($total_count);
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return 0;
        }
    }

    function get_reference($id)
    {
        $data       = "Select * from `careers_reference` where apply_form_id = " . $id;
        $qry1       = $this->dbh->prepare($data);
        $qry1->execute();
        return $page_info1 = $qry1->fetchAll(PDO::FETCH_ASSOC);
    }

    function get_title($table, $id)
    {
        global $dbh;
        if ($id != '' || $id != null)
        {
            $qry = $this->dbh->prepare("select c.* from " . $table . " c where c.id = " . $id . " order by id ASC");
            $qry->execute();
            if ($qry->rowCount() > 0)
            {
                $country_name = $qry->fetch(PDO::FETCH_ASSOC);
                return $country_name['title'];
            }
            else
            {
                return "";
            }
        }
        else
        {
            return '';
        }
    }

    function get_column_field($table, $id, $column = '')
    {
        global $dbh;
        if ($id != '' || $id != null)
        {
            $qry = $this->dbh->prepare("select c.* from " . $table . " c where c.id = " . $id . " order by id ASC");
            $qry->execute();
            if ($qry->rowCount() > 0)
            {
                $column_title = $qry->fetch(PDO::FETCH_ASSOC);
                return $column_title[$column];
            }
            else
            {
                return "";
            }
        }
        else
        {
            return '';
        }
    }

    function changeOrderCat($id, $parent_id, $position)
    {
        global $dbh;
        if (isset($id))
        {
            $qry = $this->dbh->prepare("update categories set parent_id = $parent_id, position = $position where id =" . $id);
            $qry->execute();
        }
        else
        {
            //echo $parent_id;
        }
    }

    function getTenderReports($table, $conds = array(), $single = false)
    {
        global $advance_filters;
        if (count($advance_filters) > 0)
        {
            if (isset($_GET) && count($_GET) > 0)
            {
                foreach ($_GET as $key => $value)
                {
                    if (substr($key, 0, 2) == 'a_')
                    {
                        $conds[str_replace('a_', '', $key)] = $value;
                    }

                    if (substr($key, 0, 10) == 'date_from_')
                    {
                        $dates = explode('-', $value);
                        $dt    = explode('/', trim($dates[0]));
                        $fdate = $dt[2] . '-' . $dt[1] . '-' . $dt[0];

                        $dt1        = explode('/', trim($dates[1]));
                        $tdate      = $dt1[2] . '-' . $dt1[1] . '-' . $dt1[0];
                        $this->hqry .= " and `" . str_replace('date_from_', '', $key) . "` between '" . $fdate . "' and '" . $tdate . "' ";
                    }
                }
            }
        }

        $limitsql = "";
        $nqry     = "";
        $mqry     = '';
        $qrydata  = array();
        $i        = 0;
        foreach ($conds as $key => $val)
        {
            $mqry                   .= " and $key = :bind$i";
            $qrydata[("bind" . $i)] = $val;
            $i++;
        }
        $mqry .= $this->middle_queries();
        $qry  = "select SQL_CALC_FOUND_ROWS * from $table
                            where 1 $mqry";
        $stmt = $this->dbh->prepare($qry);
        $stmt->execute($qrydata);

        $res             = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt            = $this->dbh->query('SELECT FOUND_ROWS() as cnt');
        $count           = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->totalrows = $count['cnt'];
        return $res;
    }

    function DeleteFromTable($field, $table, $where = 0, $fk = '', $fk_value = 0, $image = false, $image_field = '', $image_path = '')
    {
        $deleteQuery = "delete from `$table` ";
        if ($where > 0)
        {
            $deleteQuery .= " where `$field` = $where";
        }
        if ($fk != '' && $fk_value > 0)
        {
            $deleteQuery .= " where `$fk` = $fk_value";
        }
        if ($image == true && $image_field != '' && $image_path != '')
        {
            $selqry = "select $image_field from $table ";
            if ($where > 0)
            {
                $selqry .= " where `$field` = $where";
            }
            if ($fk != '' && $fk_value > 0)
            {
                $selqry .= " where `$fk` = $fk_value";
            }
            $qry1 = $this->dbh->prepare($selqry);
            $qry1->execute();
            $data = $qry1->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $image)
            {
                if ($image[$image_field] != '')
                {
                    if (file_exists('../' . $image_path . $image[$image_field]))
                    {
                        unlink('../' . $image_path . $image[$image_field]);
                    }
                    if (file_exists('../' . $image_path . 'thumbs/' . $image[$image_field]))
                    {
                        unlink('../' . $image_path . 'thumbs/' . $image[$image_field]);
                    }
                }
            }
        }
        $qry   = $this->dbh->prepare($deleteQuery);
        $check = $qry->execute();
    }

//        function ChangeStatus($table, $unique_field, $unique_field_value, $status_field, $status_value, $position = false)
//        {
//
//            if ($status_value == '0')
//            {
//                $changeStatus = $this->dbh->prepare("update $table set $status_field = '$status_value' where `$unique_field` = :unique_field_value");
//                $check        = $changeStatus->execute(array(':unique_field_value' => $unique_field_value));
//            }
//            else
//            {
//                $changeStatus = $this->dbh->prepare("update $table set $status_field = '$status_value', published_by = " . $_SESSION['admin_data']['id'] . ",published_date = '" . CURRDATE . "' where `$unique_field` = :unique_field_value");
//                $check        = $changeStatus->execute(array(':unique_field_value' => $unique_field_value));
//            }
//
//
//            if ($position)
//            {
//                $changePos = $this->dbh->prepare("update $table set `position` = '0' where `$unique_field` = :unique_field_value");
//                $check     = $changePos->execute(array(':unique_field_value' => $unique_field_value));
//
//                $changePos1 = $this->dbh->prepare("update $table set `position` = position-1 where `$unique_field` > :unique_field_value and position !=0 ");
//                $check      = $changePos1->execute(array(':unique_field_value' => $unique_field_value));
//            }
//        }


    function ChangeStatus($table, $unique_field, $unique_field_value, $status_field, $status_value, $position = false)
    {

        if ($status_value == '0')
        {
            $changeStatus = $this->dbh->prepare("update $table set $status_field = '$status_value' where `$unique_field` = :unique_field_value");
            $check        = $changeStatus->execute(array(':unique_field_value' => $unique_field_value));
        }
        else
        {
            $changeStatus = $this->dbh->prepare("update $table set $status_field = '$status_value' where `$unique_field` = :unique_field_value");
            $check        = $changeStatus->execute(array(':unique_field_value' => $unique_field_value));
        }


        if ($position)
        {
            $changePos = $this->dbh->prepare("update $table set `position` = '0' where `$unique_field` = :unique_field_value");
            $check     = $changePos->execute(array(':unique_field_value' => $unique_field_value));

            $changePos1 = $this->dbh->prepare("update $table set `position` = position-1 where `$unique_field` > :unique_field_value and position !=0 ");
            $check      = $changePos1->execute(array(':unique_field_value' => $unique_field_value));
        }
    }

    function CreateMSG($reason, $object, $type)
    {
        $res           = array();
        $res['msg']    = "$type : $reason $object";
        $res['result'] = "$type";
        return $res;
    }

    function FileUpload($file, $path, $rename = false, $resize = false, $resize_width = 460)
    {
        $res = array();
        if ($file['name'])
        {
            if (!$file['error'])
            {
                $new_file_name = strtolower($file['tmp_name']);
                if ($file['size'] > (10240000))
                {
                    $res['emsg'] = 'Image file too large';
//                    $resize = true;
//                    $resize_width = 1000;
                }
                else
                {

                    if ($rename == true)
                    {
                        $type = $file['type'];
                        $ext  = '';
                        if ($type != 'image/jpeg' && $resize == true)
                        {
                            $ext = '.jpg';
                        }
                        $random            = rand(50000, 100000);
                        $target_path       = $path . $random . preg_replace('/[^A-Za-z0-9\.]/', "_", basename($file['name']));
                        $res['image_name'] = $random . preg_replace('/[^A-Za-z0-9\.]/', "_", $file['name']) . $ext;
                    }
                    else
                    {
                        $target_path       = $path . preg_replace('/[^A-Za-z0-9\.]/', "_", basename($file['name']));
                        $res['image_name'] = preg_replace('/[^A-Za-z0-9\.]/', "_", $file['name']);
                    }
                    if (move_uploaded_file($file['tmp_name'], $target_path))
                    {
                        $thumb_path = $path . 'thumbs/';
                        if ($resize_width == 1000)
                        {
                            $thumb_path = $path;
                        }
                        $source      = $path . $random . preg_replace('/[^A-Za-z0-9\.]/', "_", basename($file['name']));
                        $destination = $source . $ext;

                        if ($resize == true)
                        {
                            $this->make_thumb($source, $destination, $resize_width, 0, 90);
                            if ($type != 'image/jpeg')
                            {
                                unlink($source);
                            }
                        }
                        if ($resize_width !== 1000 && file_exists($thumb_path))
                        {
                            $destination2 = $thumb_path . $random . preg_replace("/[^A-Za-z0-9\.]/", "_", basename($file['name'])) . $ext;
                            $this->make_thumb($destination, $destination2, 240, 240, 75, 4);
                        }
                        $res['smsg'] = 'Successfully uploaded';
                    }
                    else
                    {
                        $res['emsg'] = 'Error uploading the image file, try again later';
                    }
                }
            }
            else
            {
                $res['emsg'] = 'Your upload triggered the following error:  ' . $file['error'];
            }
        }
        return $res;
    }

    function FileUpload_png($file, $path, $rename = false, $resize = false, $resize_width = 460)
    {
        $res = array();
        if ($file['name'])
        {
            if (!$file['error'])
            {
                $new_file_name = strtolower($file['tmp_name']);
                if ($file['size'] > (10240000))
                {
                    $res['emsg'] = 'Image file too large';
                }
                else
                {

                    if ($rename == true)
                    {
                        $type = $file['type'];
                        $ext  = '';
                        $ext1 = '';

                        if ($type != 'image/png' && $resize == true)
                        {
                            //$ext = '.png';
                            return false;
                            exit;
                        }

                        $random            = rand(50000, 100000);
                        $target_path       = $path . $random . preg_replace('/[^A-Za-z0-9\.]/', "_", basename($file['name']));
                        $res['image_name'] = $random . preg_replace('/[^A-Za-z0-9\.]/', "_", $file['name']);
                    }
                    else
                    {
                        $target_path       = $path . preg_replace('/[^A-Za-z0-9\.]/', "_", basename($file['name']));
                        $res['image_name'] = preg_replace('/[^A-Za-z0-9\.]/', "_", $file['name']);
                    }
                    if (move_uploaded_file($file['tmp_name'], $target_path))
                    {
                        $thumb_path = $path . 'thumbs/';
                        if ($resize_width == 1000)
                        {
                            $thumb_path = $path;
                        }
                        $source      = $path . $random . preg_replace('/[^A-Za-z0-9\.]/', "_", basename($file['name']));
                        $destination = $source;

                        if ($resize == true)
                        {
                            $this->make_thumb($source, $destination, $resize_width, 0, 90);
                            if ($type != 'image/png' && $type != 'image/jpg')
                            {
                                unlink($source);
                            }
                        }
                        if ($resize_width !== 1000 && file_exists($thumb_path))
                        {
                            $destination2 = $thumb_path . $random . preg_replace("/[^A-Za-z0-9\.]/", "_", basename($file['name']));

                            $this->make_thumb($destination, $destination2, 240, 240, 75, 4);
                        }
                        $res['smsg'] = 'Successfully uploaded';
                    }
                    else
                    {
                        $res['emsg'] = 'Error uploading the image file, try again later';
                    }
                }
            }
            else
            {
                $res['emsg'] = 'Your upload triggered the following error:  ' . $file['error'];
            }
        }
        return $res;
    }

    function make_thumb($src, $dest, $width, $height, $quality, $mode = 3)
    {

        //******* ----  mode ---- 0=exact,1=portrait,2=landscape,3=auto,4=crop ----- *******//
        $magicianObj = new imageLib($src);
        $magicianObj->resizeImage($width, $height, $mode);
        $magicianObj->saveImage($dest, $quality);
    }

    function make_thumb1($src, $dest, $width, $height, $quality, $mode = 3)
    {

        $resize = new ResizeImage($src);
        $resize->resizeTo($width, $height, 'exact');
        $resize->saveImage($dest);
    }

    function VerifyUnique($table, $field, $value, $where = '', $whereCheck = '', $where2 = '', $whereCheck2 = '')
    {

        $qry = "select $field from $table where 1 = 1 and `$field` = :value ";
        if ($where != '')
        {
            $qry .= " and $where = '$whereCheck'";
        }
        if ($where2 != '')
        {
            $qry .= " and $where2 != '$whereCheck2'";
        }
        $qry          .= " and status  != '2' ";
        $verifyUnique = $this->dbh->prepare($qry);
        $verifyUnique->execute(array(':value' => $value));
        if ($verifyUnique->rowCount() > 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    function CheckStatus($table, $unique_id, $unique_id_value, $status_field)
    {

        $qry         = "select `$status_field` from $table where `$unique_id` = :value";
        $checkStatus = $this->dbh->prepare($qry);
        $checkStatus->execute(array(':value' => $unique_id_value));
        $data        = $checkStatus->fetch(PDO::FETCH_ASSOC);
        $status2     = $data[$status_field];
        return $status2;
    }

    function PrintActiveInactiveButton($table, $unique_id, $unique_id_value, $status_field, $div_id, $order_by, $order_type, $limit, $search_term, $page_id, $link)
    {
        $statusChk = $this->CheckStatus($table, $unique_id, $unique_id_value, $status_field);
        if ($statusChk == '0' || $statusChk == "Inactive")
        {
            ?>
            <i title="Inactive" onclick='return changeStatus("<?php echo $table ?>", "<?php echo $unique_id ?>",<?php echo $unique_id_value ?>, "<?php echo $div_id ?>", "<?php echo $order_type ?>", "<?php echo $order_by ?>", "<?php echo $limit ?>", "<?php echo $search_term ?>", "<?php echo $page_id; ?>", "<?php echo $link; ?>");' class='fas fa-minus-square pointer'></i>
            <?php
        }
        else if ($statusChk == '1' || $statusChk == "Active")
        {
            ?>
            <i title="Active" onclick='return changeStatus("<?php echo $table ?>", "<?php echo $unique_id ?>",<?php echo $unique_id_value ?>, "<?php echo $div_id ?>", "<?php echo $order_type ?>", "<?php echo $order_by ?>", "<?php echo $limit ?>", "<?php echo $search_term ?>", "<?php echo $page_id; ?>", "<?php echo $link; ?>");' class='far fa-check-square pointer'></i>
            <?php
        }
    }

    function PrintActiveInactiveButton2($data, $unique_id_value, $status_field)
    {
        $statusChk = $this->CheckStatus($data['table_name'], $data['unique_key_field'], $unique_id_value, $status_field);
        if ($statusChk == '0' || $statusChk == "Inactive")
        {
            ?>
            <i title="Inactive" onclick='return changeStatus("<?php echo $data['table_name'] ?>", "<?php echo $data['unique_key_field'] ?>",<?php echo $unique_id_value ?>, "<?php echo $data['div_id'] ?>", "<?php echo $data['order_type'] ?>", "<?php echo $data['order_by'] ?>", "<?php echo $data['limit'] ?>", "<?php echo $data['search_term'] ?>", "<?php echo $data['page_id']; ?>", "<?php echo $data['link']; ?>");' class='fas fa-minus-square pointer'></i>
            <?php
        }
        else if ($statusChk == '1' || $statusChk == "Active")
        {
            ?>
            <i title="Active" onclick='return changeStatus("<?php echo $data['table_name'] ?>", "<?php echo $data['unique_key_field'] ?>",<?php echo $unique_id_value ?>, "<?php echo $data['div_id'] ?>", "<?php echo $data['order_type'] ?>", "<?php echo $data['order_by'] ?>", "<?php echo $data['limit'] ?>", "<?php echo $data['search_term'] ?>", "<?php echo $data['page_id']; ?>", "<?php echo $data['link']; ?>");' class='far fa-check-square pointer'></i>
            <?php
        }
    }

    function PrintDeleteButton($data, $unique_id_value)
    {
        ?>
        <a title="Delete" href="javascript:void(0)" onclick="AjaxDelete2('<?php echo $data['table_name'] ?>', '<?php echo $data['unique_key_field'] ?>',<?php echo $unique_id_value ?>, '<?php echo $data['div_id'] ?>', '<?php echo $data['order_type'] ?>', '<?php echo $data['order_by'] ?>', '<?php echo $data['limit'] ?>', '<?php echo $data['search_term'] ?>', '<?php echo $data['page_id'] ?>', '<?php echo $data['link'] ?>');"><i class="fas fa-times  pointer"></i></a>
        <?php
    }

    function PrintButtons($data, $unique_id_value, $type = 'status')
    {

        if ($type == 'status_button')
        {
            $statusChk = $this->CheckStatus($data['table_name'], $data['unique_key_field'], $unique_id_value, $data['status_field']);

            if ($statusChk == 0)
            {
                ?>
                <a onclick='return changeStatus("<?php echo $data['table_name'] ?>", "<?php echo $data['unique_key_field'] ?>",<?php echo $unique_id_value ?>, "<?php echo $data['div_id'] ?>", "<?php echo $data['order_type'] ?>", "<?php echo $data['order_by'] ?>", "<?php echo $data['limit'] ?>", "<?php echo $data['search_term'] ?>", "<?php echo $data['page_id']; ?>", "<?php echo $data['link']; ?>");' class='btn btn-danger'> Publish</a>
                <?php
            }
            else if ($statusChk == 1)
            {
                ?>
                <a onclick='return changeStatus("<?php echo $data['table_name'] ?>", "<?php echo $data['unique_key_field'] ?>",<?php echo $unique_id_value ?>, "<?php echo $data['div_id'] ?>", "<?php echo $data['order_type'] ?>", "<?php echo $data['order_by'] ?>", "<?php echo $data['limit'] ?>", "<?php echo $data['search_term'] ?>", "<?php echo $data['page_id']; ?>", "<?php echo $data['link']; ?>");' class='btn btn-success'> Unpublish</a>
                <?php
            }
        }
        else if ($type == 'status')
        {
            $statusChk = $this->CheckStatus($data['table_name'], $data['unique_key_field'], $unique_id_value, $data['status_field']);
            if ($statusChk == '0' || $statusChk == "Inactive")
            {
                ?>
                <i title="Inactive" onclick='return changeStatus("<?php echo $data['table_name'] ?>", "<?php echo $data['unique_key_field'] ?>",<?php echo $unique_id_value ?>, "<?php echo $data['div_id'] ?>", "<?php echo $data['order_type'] ?>", "<?php echo $data['order_by'] ?>", "<?php echo $data['limit'] ?>", "<?php echo $data['search_term'] ?>", "<?php echo $data['page_id']; ?>", "<?php echo $data['link']; ?>");' class=' fa-minus-square fa pointer'></i>
                <?php
            }
            else if ($statusChk == '1' || $statusChk == "Active")
            {
                ?>
                <i title="Active" onclick='return changeStatus("<?php echo $data['table_name'] ?>", "<?php echo $data['unique_key_field'] ?>",<?php echo $unique_id_value ?>, "<?php echo $data['div_id'] ?>", "<?php echo $data['order_type'] ?>", "<?php echo $data['order_by'] ?>", "<?php echo $data['limit'] ?>", "<?php echo $data['search_term'] ?>", "<?php echo $data['page_id']; ?>", "<?php echo $data['link']; ?>");' class='fa-check-square-o fa pointer'></i>
                <?php
            }
        }
        else if ($type == 'edit')
        {
            if ($data['add_edit_link'] != '')
            {
                ?>
                <a title="Edit" href="<?php echo $data['add_edit_link'] . "?" . $data['unique_key_field'] . "=" . $unique_id_value . '&edit=1'; ?>"><i class="fa fa-edit pointer"></i></a>&nbsp;&nbsp;&nbsp;
                <?php
            }
        }
        else if ($type == 'delete')
        {
            ?>
            <a title="Delete" href="javascript:void(0)" onclick="AjaxDelete2('<?php echo $data['table_name'] ?>', '<?php echo $data['unique_key_field'] ?>',<?php echo $unique_id_value ?>, '<?php echo $data['div_id'] ?>', '<?php echo $data['order_type'] ?>', '<?php echo $data['order_by'] ?>', '<?php echo $data['limit'] ?>', '<?php echo $data['search_term'] ?>', '<?php echo $data['page_id'] ?>', '<?php echo $data['link'] ?>');"><i class="fa fa-remove  pointer"></i></a>
            <?php
        }
        else if ($type == 'edit_delete')
        {
            self::PrintButtons($data, $unique_id_value, 'edit');
            self::PrintButtons($data, $unique_id_value, 'delete');
        }
        else if ($type == 'all')
        {
            self::PrintButtons($data, $unique_id_value, 'status');
            self::PrintButtons($data, $unique_id_value, 'edit');
            self::PrintButtons($data, $unique_id_value, 'delete');
        }
    }

    function ApplyOrder($data)
    {
        ?>
        <script type="text/javascript">
            applyOrder('<?php echo $data['order_type']; ?>', '<?php echo $data['order_by']; ?>', '<?php echo $data['div_id'] ?>', '<?php echo $data['limit'] ?>', '<?php echo $data['search_term'] ?>', '<?php echo $data['page_id']; ?>', '<?php echo $data['link']; ?>')
        </script>
        <?php
    }

    function GetAdminInfo($id)
    {
        $qry       = "select * from careers_admin where `id` = :id";
        $adminInfo = $this->dbh->prepare($qry);
        $adminInfo->execute(array(':id' => $id));
        $data      = $adminInfo->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    function EditAdminInfo($username, $email, $admin_image, $password = '', $password2 = '')
    {
        if ($admin_image['name'] != '' && $admin_image['tmp_name'] != '')
        {
            $image_upload = $this->FileUpload($admin_image, 'file-manager/admin/', true);
            $qry3         = "update careers_admin set image = :image where id = :id";
            $img          = $this->dbh->prepare($qry3);
            $img->execute(array(':image' => $image_upload['image_name'], ':id' => $_SESSION['admin_data']['id']));
        }
        $qry       = "update careers_admin set user_name = :user_name, email = :email where id = :id";
        $editAdmin = $this->dbh->prepare($qry);
        $editAdmin->execute(array(':user_name' => $username, ':email' => $email, ':id' => $_SESSION['admin_data']['id']));
        if ($password != '' && $password2 != '')
        {
            $qry2       = "update careers_admin set password = :password where id = :id";
            $passChange = $this->dbh->prepare($qry2);
            $password2  = md5($password2 . PWD_STRING);
            $passChange->execute(array(':password' => $password2, ':id' => $_SESSION['admin_data']['id']));
        }
        $_SESSION['smsg'] = 'Profile successfully edited';
        header('location:edit_admin_profile.php');
        exit;
    }

    function PrintSortingClass($order_type, $org_order_type, $order_by)
    {
        if ($order_type == $org_order_type)
        {
            if ($order_by == 'ASC')
            {
                echo "class='sorting_asc'";
            }
            else if ($order_by == 'DESC')
            {
                echo "class='sorting_desc'";
            }
        }
        else
        {
            echo "class='sorting'";
        }
    }

    function test_function($id)
    {

        $qry       = "select * from careers_categories where `cat_master_id` = :id";
        $adminInfo = $this->dbh->prepare($qry);
        $adminInfo->execute(array(':id' => $id));
        return $data      = $adminInfo->fetchAll(PDO::FETCH_ASSOC);
    }

    function GetHeader($data)
    {
        global $advance_filters;
        $div_id      = $data['div_id'];
        $order_by    = $data['order_by'];
        $order_type  = $data['order_type'];
        $limit       = $data['limit'];
        $search_term = $data['search_term'];
        $page_id     = $data['page_id'];
        $link        = $data['link'];
        $limitArray  = array(1 => 10, 2 => 30, 3 => 50, 4 => 100, 5 => 2);
        $mg          = 0;
        if (@count((array) $advance_filters) > 0)
        {
            $cnt = count($advance_filters[0]);
            $typ_career = $advance_filters[0][$cnt-1];
            
            ?>
            <div class="advance_filter_div">

                <label>Advance Filters</label> <br/>
                <div class="row">
                    <?php
                    $this->getAdvanceFilters($advance_filters);
                    $cid = 0;
                    if($typ_career=='career'){
                        
                        $cid = $_GET['a_career_id'];
                    }
                    ?>
                    
                    <button type='button' onclick='return resetAdvanceFilters(<?=$cid;?>)' class='btn btn-success'>Reset</button>
                </div>
            </div>
            <br/>
            <?php
            $mg              = 1;
            $advance_filters = NULL;
        }
        ?>
        <link href="dist/css/dataTables.bootstrap.css" rel="stylesheet" type="text/css" />
        <div class="row">
            <div style="" class="col-sm-6">
                <div id="data-table_length" class="dataTables_length">
                    <label>
                        <select class="form-control input-sm" onchange="AjaxGrid(this.value, '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', '<?php echo $search_term; ?>', 1, '<?php echo $link ?>')" name="data-table_length" size="1" aria-controls = "data-table">
                            <?php
                            for ($i = 0; $i < count($limitArray); $i++)
                            {
                                $selected = '';
                                if ($limit == $limitArray[$i + 1])
                                {
                                    $selected = "selected='selected'";
                                }
                                echo "<option " . $selected . " value='" . $limitArray[$i + 1] . "'>" . $limitArray[$i + 1] . "</option>";
                            }
                            ?>
                        </select> records per page
                    </label>
                </div>
            </div>
            <div class="col-md-4 pull-right"
            <?php
            if ($mg == 1)
            {
                ?>
                     style="margin-top: -80px;"
                     <?php
                 }
                 ?>
                 >
                <form id="form1" action="" method="" style="">
                    <div class="dataTables_filter" id="data-table_filter">
                        <label><input placeholder="Search using name, email, job id" style="margin-left: 0" class="form-control input-sm"  id="search_field" onblur="" type="text" value="<?php echo $search_term ?>" aria-controls="data-table">&nbsp;&nbsp;&nbsp;<button class="btn btn-info" style="padding: 5px" type="submit" onclick="AjaxGrid('<?php echo $limit ?>', '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', document.getElementById('search_field').value,<?php echo $page_id ?>, '<?php echo $link ?>');">Go</button></label>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    function GetFooter($data)
    {
        $div_id      = $data['div_id'];
        $order_by    = $data['order_by'];
        $order_type  = $data['order_type'];
        $limit       = $data['limit'];
        $search_term = $data['search_term'];
        $count       = $data['count'];
        $page_id     = $data['page_id'];
        $link        = $data['link'];

        $pagination_count = ceil($count / $limit);
        if ($limit > $count || $limit == $count || $page_id == $pagination_count)
        {
            $end = $count;
        }
        else
        {
            $end = $page_id * $limit;
        }
        if ($pagination_count >= 7)
        {
            $pagination_limit = 7;
        }
        else
        {
            $pagination_limit = $pagination_count;
        }
        $start = (($page_id * $limit) - $limit) + 1;
        if ($count == 0)
        {
            $start = 0;
        }
        ?>

        <div class="row">
            <div class="col-sm-5">
                <div class="dataTables_info" id="data-table_info">Showing <?php echo $start; ?> to <?php echo $end; ?> of <?php echo $count; ?> entries
                </div>
            </div>
            <div class="col-sm-7">
                <div class="dataTables_paginate paging_simple_numbers">
                    <ul class="pagination">
                        <li <?php
                        if ($page_id == 1)
                        {
                            echo 'class="disabled prev"';
                        }
                        else
                        {
                            ?>
                                onclick="AjaxGrid('<?php echo $limit ?>', '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', '<?php echo $search_term; ?>', 1, '<?php echo $link; ?>');"
                                <?php
                                echo 'class="prev"';
                            }
                            ?>><a href="javascript:void(0)">First</a></li>
                        <li <?php
                        if ($page_id == 1)
                        {
                            echo 'class="disabled prev"';
                        }
                        else
                        {
                            ?>
                                onclick="AjaxGrid('<?php echo $limit ?>', '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', '<?php echo $search_term; ?>', <?php echo $page_id - 1; ?>, '<?php echo $link; ?>');"
                                <?php
                                echo 'class="prev"';
                            }
                            ?>><a href="javascript:void(0)">Previous</a></li>
                            <?php
                            for ($i = $page_id - 3; $i <= $pagination_limit + ($page_id - 1); $i++)
                            {
                                if ($i > $pagination_count || $i < 1)
                                {

                                }
                                else
                                {
                                    ?>
                                <li class="<?php
                                if ($i == $page_id)
                                {
                                    echo 'active';
                                }
                                ?>"><a onclick="AjaxGrid('<?php echo $limit ?>', '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', '<?php echo $search_term; ?>', <?php echo $i; ?>, '<?php echo $link; ?>');" href="javascript:void(0)"><?php echo $i; ?></a></li>
                                    <?php
                                }
                            }
                            ?>
                        <li <?php
                        if ($page_id == $pagination_count || $count == 0)
                        {
                            echo 'class="disabled next"';
                        }
                        else
                        {
                            echo "class='next'";
                            ?>
                                onclick="AjaxGrid('<?php echo $limit ?>', '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', '<?php echo $search_term; ?>', <?php echo $page_id + 1; ?>, '<?php echo $link; ?>');"
                                <?php
                            }
                            ?>><a href="javascript:void(0)">Next </a></li>
                        <li <?php
                        if ($page_id == $pagination_count || $count == 0)
                        {
                            echo 'class="disabled next"';
                        }
                        else
                        {
                            echo "class='next'";
                            ?>
                                onclick="AjaxGrid('<?php echo $limit ?>', '<?php echo $div_id ?>', '<?php echo $order_by ?>', '<?php echo $order_type ?>', '<?php echo $search_term; ?>', <?php echo $pagination_count; ?>, '<?php echo $link; ?>');"
                                <?php
                            }
                            ?>><a href="javascript:void(0)">Last</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        $this->ApplyOrder($data);
    }

    function customFilter($title, $type, $conds = array(), $ftype = 'drop', $tbl_name = '', $ftitle = 'title')
    {
        ?>
        <div style="margin-bottom: 10px; width:360px" class="col-md-2">
            <?php
            if ($ftype == 'drop')
            {
                ?>
                <select class="advance_filter_drop form-control select2" name="a_<?= $type ?>" data-id="a_<?= $type ?>" onchange="return advanceFilters(this.value)">
                    <option value=""><?= $title; ?></option>
                    <?php
                    foreach ($conds as $key => $value)
                    {
                        ?>
                        <option
                        <?php
                        if (isset($_GET['a_' . $type]) && $_GET['a_' . $type] == $key)
                        {
                            echo 'selected="selected"';
                        }
                        ?>
                            value="<?= $key ?>"><?= $value ?></option>
                            <?php
                        }
                        ?>
                </select>
                <?php
            }
            elseif ($ftype == 'date')
            {
                ?>
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input <?php
                        if (isset($_GET['date_from_' . $type]) && $_GET['date_from_' . $type] != '')
                        {
                            echo "value='" . $_GET['date_from_' . $type] . "'";
                        }
                        ?>
                            onchange="return advanceFilters()" name="date_from_<?= $type ?>" id="date_from_<?= $type ?>" data-id="date_from_<?= $type ?>" placeholder="From - To <?php echo $title ?>" type="text" class="form-control pull-right advance_filter_date" />
                    </div>
                </div>
                <script>
                    $('#date_from_<?= $type; ?>').daterangepicker({
                        format: 'DD/MM/YYYY'
                    });
                </script>
                <?php
            }
            elseif ($ftype == 'table')
            {
                $opts  = $this->getTable2($tbl_name, $conds);
                $conds = array();
                foreach ($opts as $op)
                {
                    $conds[$op['id']] = $op[$ftitle];
                }
                ?>
                <select class="advance_filter_drop form-control select2" name="a_<?= $type ?>" data-id="a_<?= $type ?>" onchange="return advanceFilters(this.value)">
                    <option value=""><?= $title; ?></option>
                    <?php
                    foreach ($conds as $key => $value)
                    {
                        ?>
                        <option
                        <?php
                        if (isset($_GET['a_' . $type]) && $_GET['a_' . $type] == $key)
                        {
                            echo 'selected="selected"';
                        }
                        ?>
                            value="<?= $key ?>"><?= $value ?></option>
                            <?php
                        }
                        ?>
                </select>
                <?php
            }
            ?>

        </div>
        <?php
    }

    function getAdvanceFilters($advanceFilters)
    {
        foreach ($advanceFilters as $af)
        {
            $type     = 'drop';
            $tbl_name = '';
            $title    = 'title';
            if (isset($af[3]) && $af[3] != '')
            {
                $type = $af[3];
            }
            if (isset($af[4]) && $af[4] != '')
            {
                $tbl_name = $af[4];
            }
            if (isset($af[5]) && $af[5] != '')
            {
                $title = $af[5];
            }
            $this->customFilter($af[0], $af[1], $af[2], $type, $tbl_name, $title);
        }
    }

    function GetTableIds($table, $id)
    {

        $qry  = $this->dbh->prepare("select `$id` from `$table` ");
        $qry->execute();
        $data = $qry->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    function getLastPosition($table, $unique_key, $foreign_key = '', $foreign_key_value = 0)
    {

        $qry1 = "select position from $table ";
        if ($foreign_key != '')
        {
            $qry1 .= " where $foreign_key = $foreign_key_value ";
        }
        $qry1 .= "order by position DESC limit 0,1";

        $select1 = $this->dbh->prepare($qry1);
        $select1->execute();
        if ($select1->rowCount() > 0)
        {
            $data1    = $select1->fetch();
            $position = $data1['position'] + 1;
        }
        else
        {
            $position = 1;
        }

        return $position;
    }

    function ChangePosition($table, $unique_key, $unique_key_value, $position_field, $position)
    {

        $res       = array();
        $editStore = "update $table set $position_field = $position where $unique_key = $unique_key_value";
        $qry       = $this->dbh->prepare($editStore);
        $qry->execute();
    }

    function GetCount($table, $count_what = '', $where_field = '', $where_value = '', $where_field2 = '', $where_value2 = '')
    {
        if ($count_what != '')
        {
            $count_what = $count_what;
        }
        else
        {
            $count_what = '*';
        }
        $qry = "select count($count_what) as count from $table";
        if ($where_field != '' && $where_value != '')
        {
            $qry .= " where `$where_field` = '$where_value'";
        }
        if ($where_field2 != '' && $where_value2 != '')
        {
            $qry .= " and `$where_field2` = '$where_value2'";
        }

        if (@$_SESSION['admin_data']['user_type'] == 'W')
        {
            $qry .= " and `added_by` = " . $_SESSION['admin_data']['id'];
        }
        $select = $this->dbh->prepare($qry);
        $select->execute();
        $data   = $select->fetch(PDO::FETCH_ASSOC);
        return $data['count'];
    }

    function GetSettings()
    {
        $qry    = "select * from settings";
        $select = $this->dbh->prepare($qry);
        $select->execute();
        $data   = $select->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    function write_log($message)
    {
        if (($remote_addr = $_SERVER['REMOTE_ADDR']) == '')
        {
            $remote_addr = "REMOTE_ADDR_UNKNOWN";
        }
        if (($request_uri = $_SERVER['REQUEST_URI']) == '')
        {
            $request_uri = "REQUEST_URI_UNKNOWN";
        }
        $sql = "INSERT INTO error_log (remote_addr, request_uri, message) VALUES('$remote_addr', '$request_uri','$message')";
        $log = $this->dbh->prepare($sql);
        $log->execute();
    }

    function slugify($text)
    {
        $text = preg_replace('~[^-\w]+~', '', (strtolower(trim(preg_replace('~[^\\pL\d]+~u', '-', $text), '-'))));
        if (empty($text))
        {
            return 'n-a';
        }
        return $text;
    }

//            function filter_string($text)
//            {
//                $txt = preg_replace('[^a-zA-Z0-9]/i','-',$text);
//
//                return $txt;
//            }

    function filter_string($text)
    {
        $txt = preg_replace('/[^A-Za-z0-9\-\_]/', ' ', $text);
        return $txt;
    }

    function email_validation($email)
    {
        $email = str_replace(' ', '', $email);

        $email_array = explode(',', $email);
        foreach ($email_array as $em)
        {
            if (filter_var($em, FILTER_VALIDATE_EMAIL))
            {
                $filter_emails[] = $em;
            }
        }
        return implode(',', $filter_emails);
    }

    function getDrop($config = array())
    {
        $conds     = array();
        $data_tags = '';
        $name_id   = '';
        if (isset($config['name']) && $config['name'] != '')
        {
            $nm = $config['name'];
            if (isset($config['multiple']) && $config['multiple'] == 'multiple')
            {
                $nm = $config['name'] . "[]";
            }
            $name_id = "name='" . $nm . "' id='" . $config['name'] . "' ";
        }
        else
        {
            $nm = $config['table_name'];
            if (isset($config['multiple']) && $config['multiple'] == 'multiple')
            {
                $nm = $config['table_name'] . "[]";
            }
            $name_id = "name='" . $nm . "' id='" . $config['table_name'] . "' ";
        }
        if (isset($config['conds']))
        {
            $conds = $config['conds'];
        }
        if (isset($config['orderby']))
        {
            $this->orderby = $config['orderby'];
        }
        if (isset($config['ordertype']))
        {
            $this->ordertype = $config['ordertype'];
        }
        $data            = $this->getTable($config['table_name'], $conds);
        $this->orderby   = '';
        $this->ordertype = '';

        $onchange = '';
        if (isset($config['change_function']) && $config['change_function'] != '')
        {
            $onchange = "onchange='" . $config['change_function'] . "'";
        }
        if (isset($config['data_tags']) && count($config['data_tags']) > 0)
        {
            foreach ($config['data_tags'] as $key => $value)
            {
                $data_tags .= "$key='$value' ";
            }
        }

        $class_name = '';
        $multiple   = '';
        $required   = '';
        $disabled   = '';
        $readonly   = '';
        if (isset($config['class_name']) && $config['class_name'] != '')
        {
            $class_name = $config['class_name'];
        }
        if (isset($config['multiple']) && $config['multiple'] == 'multiple')
        {
            $multiple = "multiple='multiple'";
        }
        if (isset($config['required']) && $config['required'] == 'required')
        {
            $required = "required='required'";
        }
        if (isset($config['disabled']) && $config['disabled'] == 'disabled')
        {
            $disabled = "disabled='disabled'";
        }
        if (isset($config['readonly']) && $config['readonly'] == 'readonly')
        {
            $readonly = "readonly='readonly'";
        }
        $str = "<select $data_tags $multiple $required $disabled $readonly class='$class_name' $onchange $name_id>";
        if (!isset($config['multiple']) || $config['multiple'] !== 'multiple')
        {
            $str .= "<option value=''>Select</option>";
        }
        $i = 0;
        foreach ($data as $d)
        {
            $selected = '';
            if (isset($config['selected_value']) && isset($config['selected_field']) && $config['selected_field'] != '')
            {
                if (isset($config['multiple']) && $config['multiple'] == 'multiple')
                {
                    if (in_array($d[$config['selected_field']], $config['selected_value']))
                    {
                        $selected = 'selected="selected"';
                    }
                }
                else
                {
                    if (is_array($config['selected_value']))
                    {
                        if (in_array($d[$config['selected_field']], $config['selected_value']))
                        {
                            $selected = 'selected="selected"';
                        }
                    }
                    else
                    {
                        if ($d[$config['selected_field']] == $config['selected_value'])
                        {
                            $selected = 'selected="selected"';
                        }
                    }
                }
            }
//            if ($selected == '' && $i == 0 && !isset($config['multiple']))
//            {
//                $selected = 'selected="selected"';
//            }
            $value = 'id';
            if (isset($config['id_field']))
            {
                $value = $config['id_field'];
            }
            $value_field = 'name';
            if (isset($config['value_field']))
            {
                $value_field = $config['value_field'];
            }
            if (isset($config['selected_field']) && $config['selected_field'] != '')
            {
                $value = $config['selected_field'];
            }
            $str .= "<option $selected value='" . $d[$value] . "'>" . ucwords($d[$value_field]) . "</option>";
            $i++;
        }
        $str .= "</select>";
        return $str;
    }

    function getMaxField($conds)
    {
        $data = $this->getTable($conds['table_name'], $conds['conds'], true);
        return $data[$conds['field_name']] + 1;
    }

    function insertMeta($data)
    {
        $tabledata = array(
            "d_table_name" => 'meta'
        );

        $this->addEditTable($data, $tabledata);
    }

    function updateMeta($title, $description, $keywords, $fk_id, $type)
    {
        $q_chk  = "SELECT id from meta where meta_ref_type = '$type' and meta_ref_id = $fk_id";
        $select = $this->dbh->prepare($q_chk);
        $select->execute();
        $data1  = $select->fetch(PDO::FETCH_ASSOC);
        if (isset($data1['id']) && $data1['id'] > 0)
        {

            $sql_edit_meta = "update meta set meta_title = ?,meta_description = ?,meta_keywords = ? where meta_ref_id = ? and meta_ref_type = ? ";
            $qry_edit_meta = $this->dbh->prepare($sql_edit_meta);
            $qry_edit_meta->execute(array($title, $description, $keywords, $fk_id, $type));
        }
        else
        {
            $metadata = array(
                'meta_title'       => $title,
                'meta_keywords'    => $keywords,
                'meta_description' => $description,
                'meta_ref_id'      => $fk_id,
                'meta_ref_type'    => $type
            );

            $this->insertMeta($metadata);
        }
    }

    function checkSlug($table, $slug, $edit, $id)
    {
        if ($id == 0 && $edit == 0)
        {
            $conds1 = array('slug' => $slug);
            $data1  = $this->getTable($table, $conds1, true);
        }
        else if ($id > 0 && $edit == 1)
        {
            $qry    = "select slug from $table where slug = '$slug' and id != $id";
            $select = $this->dbh->prepare($qry);
            $select->execute();
            $data1  = $select->fetch(PDO::FETCH_ASSOC);
        }


        if (isset($data1['slug']) && $data1['slug'] != '')
        {
            return "* Duplicate slug, Please chose another";
        }
        else
        {
            return 'ok';
        }
    }

    function checkExist($table, $field, $field_val, $id = 0)
    {
        if ($id == 0)
        {
            $conds1 = array($field => $field_val);
            $data1  = $this->getTable($table, $conds1, true);
        }
        else if ($id > 0)
        {
            $qry    = "select `$field` from $table where `$field` = '$field_val' and id != $id";
            $select = $this->dbh->prepare($qry);
            $select->execute();
            $data1  = $select->fetch(PDO::FETCH_ASSOC);
        }

        if (isset($data1[$field]) && $data1[$field] != '')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function checkExist2($table, $field, $field_val, $field2, $field_val2, $id = 0)
    {
        if ($id == 0)
        {
            $conds1 = array($field => $field_val, $field2 => $field_val2);
            $data1  = $this->getTable($table, $conds1, true);
        }
        else if ($id > 0)
        {
            $qry    = "select `$field` from $table where `$field` = '$field_val' and `$field2` = '$field_val2' and id != $id";
            $select = $this->dbh->prepare($qry);
            $select->execute();
            $data1  = $select->fetch(PDO::FETCH_ASSOC);
        }

        if (isset($data1[$field]) && $data1[$field] != '')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function checkSlugExist($table, $slug, $edit = 0, $id = 0)
    {
        if ($id == 0 && $edit == 0)
        {
            $conds1 = array('slug' => $slug);
            $data1  = $this->getTable2($table, $conds1, true);
        }
        else if ($id > 0 && $edit == 1)
        {
            $qry    = "select slug from $table where slug = '$slug' and id != $id";
            $select = $this->dbh->prepare($qry);
            $select->execute();
            $data1  = $select->fetch(PDO::FETCH_ASSOC);
        }

        if (isset($data1['slug']) && $data1['slug'] != '')
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    function removeImage($table, $where_value, $where_field, $image_field, $language_flag)
    {
        $qry = "update $table set $image_field = '' where $where_field = '$where_value' ";
        if ($language_flag == 1)
        {
            $qry .= " and language_id = " . $_SESSION['language_id'];
        }

        $select = $this->dbh->prepare($qry);
        $select->execute();
    }

    function fetchQuery($qry, $single = false)
    {
        $select = $this->dbh->prepare($qry);
        $select->execute();
        if ($single)
        {
            $data = $select->fetch(PDO::FETCH_ASSOC);
        }
        else
        {
            $data = $select->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    }

    function checkFileExist($file_name, $gallery_type)
    {
        $ds = DIRECTORY_SEPARATOR;  // Store directory separator (DIRECTORY_SEPARATOR) to a simple variable. This is just a personal preference as we hate to type long variable name.
        if ($gallery_type == 'product_images')
        {
            $storeFolder = '../../resources/admin_uploads/products/gallery/';
        }
        if ($gallery_type == 'project_images')
        {
            $storeFolder = '../../resources/admin_uploads/projects/gallery/';
        }

        $targetPath = dirname(__FILE__) . $ds . $storeFolder . $ds;

        if (file_exists($targetPath . $file_name))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function editSettings($data)
    {
        $qry    = "update careers_settings set emails = '" . $data['emails'] . "' ";
        $select = $this->dbh->prepare($qry);
        $select->execute();
    }

    function sendEmail($to, $subject, $message)
    {
        $mail             = new PHPMailer;
        $mail->isSMTP();
        $mail->Host       = '';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('', 'PAESE');
        if (is_array($to))
        {
            foreach ($to as $em)
            {
                $mail->addAddress($em);
            }
        }
        else
        {
            $mail->addAddress($to);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        if (!$mail->send())
        {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        }
        else
        {
            echo 'Message has been sent';
        }
    }

    function debug($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    function escape($string)
    {
        return trim(htmlspecialchars($string, ENT_QUOTES));
    }

    //add news
    //add new tags
    function add_tags($data, $cat_id, $title = 'tag_name')
    {
        $catname = array();
        $catname = $_POST['tag_category'];
        foreach ($catname as $c)
        {
            if (is_numeric($c))  //condition for already exist tag name in tag_master table
            {
                $tag_master_id     = $c;
                $gallery_tabledata = array(
                    "d_table_name" => 'tag_categories'
                );

                $gallery_data = array(
                    'tag_master_id' => $tag_master_id,
                    'cat_id'        => $cat_id,
                    'title'         => $title,
                );

                $this->addEditTable($gallery_data, $gallery_tabledata);
            }
            else
            {
                $tag_title = $c;
                $qry       = "select * from tag_master where title='{$tag_title}'";
                $data      = $this->fetchQuery($qry);

                foreach ($data as $d)
                {
                    $gallery_tabledata = array(
                        "d_table_name" => 'tag_categories'
                    );

                    $gallery_data = array(
                        'tag_master_id' => $d['id'],
                        'cat_id'        => $cat_id,
                        'title'         => $title,
                    );
                }
                //$this->addEditTable($gallery_data, $gallery_tabledata);
            }
        }
    }

    //update new tags
    function edit_tags($data, $slug = 'tag_name')
    {
        if (isset($data['tag_category']) && $data['tag_category'] != '')
        {
            $catname = $_POST['tag_category'];
            $tag_del = "delete from `tag_categories` where cat_id= {$_POST['id']} and title='{$slug}'";
            $ins1    = $this->dbh->prepare($tag_del);
            $ins1->execute();

            foreach ($catname as $c)
            {
                $tag_master_id = $c;
                /* to add new records into tag_master table */
                if (!is_numeric($c))
                {
//                            $title             = $c;
//                            $gallery_tabledata = array(
//                                "d_table_name" => 'tag_master'
//                            );
//
//                            $gallery_data = array(
//                                'title' => $title,
//                                'slug'  => $slug,
//                            );
//
//                            $this->addEditTable($gallery_data, $gallery_tabledata);
//
//                            if ($this->last_insert_id)
//                            {
//                                $tg_mster_id = $this->last_insert_id;
//                                $stmt        = $this->dbh->prepare("insert into `tag_categories`(tag_master_id,cat_id,title) values({$tg_mster_id},{$_POST['id']},'{$slug}')");
//                                $update      = $stmt->execute();
//                            }
                }
                else
                {
                    $gallery_tabledata = array(
                        "d_table_name" => 'tag_categories'
                    );

                    $gallery_data = array(
                        'tag_master_id' => $tag_master_id,
                        'cat_id'        => $_POST['id'],
                        'title'         => $slug,
                    );

                    $this->addEditTable($gallery_data, $gallery_tabledata);
                }
            }
        }
        else
        {

            $tag_del = "delete from `tag_categories` where cat_id= {$_POST['id']} and title='{$slug}'";
            $ins1    = $this->dbh->prepare($tag_del);
            $ins1->execute();
        }
    }

    function add_reg_category($data, $files)
    {

        $master_tabledata = array(
            "d_table_name" => 'registration_categories'
        );

        $master_data = array(
            'title'       => $data['title'],
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'price'       => $data['price'],
            'comments'    => $data['comments'],
            'block_order' => $data['block_order'],
            'add_date'    => date('Y-m-d H:i:s'),
            'status'      => 1
        );

        $this->addEditTable($master_data, $master_tabledata);
        $feature_id = $this->last_insert_id;

        /* Sort order code start */
        if ($data['block_order'] != $data['old_sort_order'])
        {

            if ($data['block_order'] < $data['old_sort_order'])
            {
                $incP  = 1;
                $query = "SELECT id  FROM registration_categories WHERE block_order >='" . $data['block_order'] . "' AND block_order <'" . $data['old_sort_order'] . "'  AND status='1' order by block_order asc";
            }
            else
            {
                $incP  = 0;
                $query = "SELECT id  FROM registration_categories WHERE block_order <='" . $data['block_order'] . "' AND block_order >'" . $data['old_sort_order'] . "'  AND status='1' order by block_order asc";
            }

            $qury12 = $this->dbh->prepare($query);
            $qury12->execute();
            $id     = $qury12->fetchAll(PDO::FETCH_ASSOC);
        }

        /* update sort order */
        $i = 0;
        if (isset($id[$i]['id']) && !empty($id[$i]['id']))
        {
            for ($i = 0; $i < count($id); $i++)
            {
                $ids = array($id[$i]['id']);
                if ($incP == 1)
                {
                    if (count($ids) == 1)
                    {
                        $query = "UPDATE registration_categories SET block_order=(block_order+1) , update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id='" . intval(current($ids)) . "' AND status='1'";
                    }
                    else
                    {
                        $ids_list = implode(",", $ids);
                        $query    = "UPDATE registration_categories SET block_order=(block_order+1), update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id IN (" . $ids_list . ") AND status='1'";
                    }

                    $quy = $this->dbh->prepare($query);
                    $quy->execute();
                }
                else if ($incP == 0)
                {
                    if (count($ids) == 1)
                    {
                        $query = "UPDATE registration_categories SET block_order=(block_order-1),
                        update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id='" . intval(current($ids)) . "' AND status='1'";
                    }
                    else
                    {
                        $ids_list = implode(",", $ids);
                        $query    = "UPDATE registration_categories SET block_order= (block_order-1), update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id IN (" . $ids_list . ") AND status='1'";
                    }

                    $quy = $obj->dbh->prepare($query);
                    $quy->execute();
                }
            }
        }
        /* Sort order code end */

        $_SESSION['smsg'] = 'Registration category added';
    }

    function edit_reg_category($data, $files)
    {

        /* Sort order code start */
        if ($data['block_order'] != $data['old_sort_order'])
        {

            if ($data['block_order'] < $data['old_sort_order'])
            {
                $incP  = 1;
                $query = "SELECT id  FROM registration_categories WHERE block_order >='" . $data['block_order'] . "' AND block_order <'" . $data['old_sort_order'] . "'  AND status='1' order by block_order asc";
            }
            else
            {
                $incP  = 0;
                $query = "SELECT id  FROM registration_categories WHERE block_order <='" . $data['block_order'] . "' AND block_order >'" . $data['old_sort_order'] . "'  AND status='1' order by block_order asc";
            }

            $qury12 = $this->dbh->prepare($query);
            $qury12->execute();
            $id     = $qury12->fetchAll(PDO::FETCH_ASSOC);
        }

        /* update sort order */
        $i = 0;
        if (isset($id[$i]['id']) && !empty($id[$i]['id']))
        {
            for ($i = 0; $i < count($id); $i++)
            {
                $ids = array($id[$i]['id']);
                if ($incP == 1)
                {
                    if (count($ids) == 1)
                    {
                        $query = "UPDATE registration_categories SET block_order=(block_order+1) , update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id='" . intval(current($ids)) . "' AND status='1'";
                    }
                    else
                    {
                        $ids_list = implode(",", $ids);
                        $query    = "UPDATE registration_categories SET block_order=(block_order+1), update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id IN (" . $ids_list . ") AND status='1'";
                    }

                    $quy = $this->dbh->prepare($query);
                    $quy->execute();
                }
                else if ($incP == 0)
                {
                    if (count($ids) == 1)
                    {
                        $query = "UPDATE registration_categories SET block_order=(block_order-1),
                        update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id='" . intval(current($ids)) . "' AND status='1'";
                    }
                    else
                    {
                        $ids_list = implode(",", $ids);
                        $query    = "UPDATE registration_categories SET block_order= (block_order-1), update_date='" . date('Y-m-d H:i:s') . "'
                        WHERE id IN (" . $ids_list . ") AND status='1'";
                    }

                    $quy = $obj->dbh->prepare($query);
                    $quy->execute();
                }
            }
        }
        /* Sort order code end */

        $status         = '0';
        $published_by   = 0;
        $published_date = NULL;

        if (isset($_POST['edit']) && $_POST['edit'] == 1)
        {
            $status         = '1';
            $published_by   = $_SESSION['admin_data']['id'];
            $published_date = CURRDATE;
        }

        $master_tabledata = array(
            "d_table_name" => 'registration_categories',
            "d_pk_name"    => 'id'
        );

        $master_data = array(
            'id'          => $data['id'],
            'title'       => $data['title'],
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'price'       => $data['price'],
            'comments'    => $data['comments'],
            'block_order' => $data['block_order'],
            'update_date' => date('Y-m-d H:i:s'),
        );

        $this->addEditTable($master_data, $master_tabledata);
        $feature_id = $data['id'];

        $_SESSION['smsg'] = 'Registration category edited';
    }

    function send_email_test($to, $subject, $message, $bcc = '')
    {

        $mail = new PHPMailer(true);

        try
        {
            $mail->IsSMTP();                           // tell the class to use SMTP
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'STARTTLS';              // enable SMTP authentication
            // $mail->SMTPDebug  = 2;              // enable SMTP authentication
            $mail->Port       = PORT; //587;                    // set the SMTP server port
            $mail->Host       = SMTPHOST; //'smtp.sparkpostmail.com'; // SMTP server
            $mail->Username   = EMAILUSER; //'SMTP_Injection';     // SMTP server username
            $mail->Password   = EMAILPASS; //'76784ae0ecd0e096f464d60de7c92fdde75a231d';            // SMTP server password
            //$mail->IsSendmail();  // tell the class to use Sendmail

            $mail->From        = EMAILFROM;
            $mail->FromName    = EMAILNAME;
            $mail->ContentType = 'text/html; charset=utf-8';

            $mail->AddAddress($to);

            if (is_array($bcc) && count($bcc) > 0)
            {
                foreach ($bcc as $bc)
                {
                    $mail->AddCC($bc);
                }
            }
            else if (!empty($bcc))
            {
                $mail->AddCC($bcc);
            }



            $mail->Subject = $subject;

            $mail->AltBody  = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
            $mail->WordWrap = 80; // set word wrap

            $mail->MsgHTML($message);

            $mail->IsHTML(true); // send as HTML

            $mail->Send();
        }
        catch (phpmailerException $e)
        {
            // mail('nishant@netlink.co.in', $subject, $e->errorMessage());
        }
        catch (Exception $e)
        {
            // mail('nishant@netlink.co.in', $subject, $e->getMessage());
        }
        // exit;
    }

    function send_email($to, $subject, $message, $fpothrmail = '', $bcc = '')
    {
        $mail = new PHPMailer(true);

        try
        {
            $mail->IsSMTP();                           // tell the class to use SMTP
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'STARTTLS';              // enable SMTP authentication
            $mail->Port       = PORT; //587;                    // set the SMTP server port
            $mail->Host       = SMTPHOST; //'smtp.sparkpostmail.com'; // SMTP server
            $mail->Username   = EMAILUSER; //'SMTP_Injection';     // SMTP server username
            $mail->Password   = EMAILPASS; //'76784ae0ecd0e096f464d60de7c92fdde75a231d';            // SMTP server password
            //$mail->IsSendmail();  // tell the class to use Sendmail

            $mail->From        = EMAILFROM;
            $mail->FromName    = EMAILNAME;
            $mail->ContentType = 'text/html; charset=utf-8';

            $mail->AddAddress($to);

            if (!empty($fpothrmail))
                $mail->AddBCC($fpothrmail);
            if (!empty($bcc))
                $mail->AddBCC($bcc);

            $mail->Subject = $subject;

            $mail->AltBody  = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
            $mail->WordWrap = 80; // set word wrap

            $mail->MsgHTML($message);

            $mail->IsHTML(true); // send as HTML
            $mail->Send();
        }
        catch (phpmailerException $e)
        {
            echo $e->errorMessage();
            //mail('krishan@netlink.co.in', $subject, $e->errorMessage());
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
            //mail('krishan@netlink.co.in', $subject, $e->getMessage());
        }
    }

    function add_page($data, $files)
    {
        $add_to_nav     = 0;
        $add_to_header  = 0;
        $add_to_footer  = 0;
        $external_link  = 0;
        $link           = '';
        $target         = '';
        $status         = '0';
        $published_by   = 0;
        $add_primary    = 0;
        $published_date = NULL;

        if (isset($data['submit']) && $data['submit'] == '1')
        {
            $status         = '1';
            $published_by   = $_SESSION['admin_data']['id'];
            $published_date = CURRDATE;
        }

        $conds    = array(
            'table_name' => 'pages',
            'field_name' => 'position',
            'conds'      => array('parent_id' => '0')
        );
        $position = $this->getMaxField($conds);

        if (isset($data['add_to_navigation']) && $data['add_to_navigation'] == 1)
        {
            $add_to_nav  = 1;
            $add_primary = 0;

            if ($_POST['add_primary'] == 'primary')
            {
                $add_primary = 1;
            }
        }
        if (isset($data['add_to_footer']) && $data['add_to_footer'] == 1)
        {
            $add_to_footer = 1;  //add_primary
        }

        if (isset($data['external_link']) && $data['external_link'] == 1)
        {
            $external_link = 1;
            $link          = $data['link'];
            $target        = $data['link_target'];
        }

        $master_tabledata = array(
            "d_table_name" => 'pages'
        );
        $master_data      = array(
            'parent_id'         => $data['parent'],
            'banner_image'      => $data['banner_image_name'],
            'page'              => $data['page_name'],
            's_date'            => date('Y-m-d', strtotime($_POST['s_date'])),
            'e_date'            => date('Y-m-d', strtotime($_POST['e_date'])),
            'title'             => $data['title'],
            's_title'           => $data['s_title'],
            'short_desc'        => $data['short_desc'],
            'template'          => $data['templates'],
            'content'           => $data['page_data'],
            'content2'          => $data['content2'],
            'content3'          => $data['content3'],
            'add_to_navigation' => $add_to_nav,
            'add_to_footer'     => $add_to_footer,
            'add_primary'       => $add_primary,
            'status'            => $status,
            'add_date'          => date('Y-m-d H:i:s'),
            'position'          => $position,
            'external_link'     => $link,
            'slug'              => $data['slug'],
            'meta_title'        => $data['seo_title'],
            'meta_description'  => $data['seo_description'],
            'meta_keywords'     => $data['seo_keywords'],
            'target'            => $target,
            'added_by'          => $_SESSION['admin_data']['id'],
            'published_by'      => $published_by,
            'published_date'    => $published_date,
        );

        $this->addEditTable($master_data, $master_tabledata);
        $page_id = $this->last_insert_id;

        if (isset($data['item_desc']) && is_array($data['item_desc']) && count($data['item_desc']) > 0)
        {
            $extra_tabledata = array(
                "d_table_name" => 'page_moredata'
            );

            for ($i = 0; $i < count($data['item_desc']); $i++)
            {
                if (isset($data['item_desc'][$i]) && $data['item_desc'][$i] != '')
                {
                    $extra_data = array(
                        'page_id'     => $page_id,
                        'description' => trim($data['item_desc'][$i])
                    );

                    $this->addEditTable($extra_data, $extra_tabledata);
                }
            }
        }

        $_SESSION['smsg'] = 'Page added';

        if (isset($data['submit_approval']) && $data['submit_approval'] == '1')
        {
            $this->send_approval('page', $data['page_name']);
        }

        if (isset($data['save_preview']) && $data['save_preview'] == '1')
        {
            $this->show_preview('page', $page_id);
        }
    }

    function edit_page($data, $files)
    {
        $add_to_nav     = 0;
        $add_to_footer  = 0;
        $external_link  = 0;
        $link           = '';
        $target         = '';
        $add_primary    = 0;
        $status         = '0';
        $published_by   = 0;
        $published_date = NULL;

        if (isset($data['submit']) && $data['submit'] == '1')
        {
            $status         = '1';
            $published_by   = $_SESSION['admin_data']['id'];
            $published_date = CURRDATE;
        }

        if (isset($data['save_preview']) && $data['save_preview'] == '1')
        {
            $status = $data['old_status'];
        }

        $conds = array(
            'table_name' => 'pages',
            'field_name' => 'position',
            'conds'      => array('parent_id' => '0')
        );

        if (isset($data['add_to_footer']) && $data['add_to_footer'] == 1)
        {
            $add_to_footer = 1;
        }

        if (isset($data['add_to_navigation']) && $data['add_to_navigation'] == 1)
        {
            $add_to_nav  = 1;
            $add_primary = 0;

            if ($_POST['add_primary'] == 'primary')
            {
                $add_primary = 1;
            }
        }


        if (isset($data['external_link']) && $data['external_link'] == 1)
        {
            $external_link = 1;
            $link          = $data['link'];
            $target        = $data['link_target'];
        }

        $master_tabledata = array(
            "d_table_name" => 'pages',
            "d_pk_name"    => 'id'
        );

        $master_data = array(
            'id'                => $data['page_id'],
            'banner_image'      => $data['banner_image_name'],
            'parent_id'         => $data['parent'],
            's_date'            => date('Y-m-d', strtotime($_POST['s_date'])),
            'e_date'            => date('Y-m-d', strtotime($_POST['e_date'])),
            'page'              => $data['page_name'],
            'title'             => $data['title'],
            's_title'           => $data['s_title'],
            'short_desc'        => $data['short_desc'],
            'content'           => $data['page_data'],
            'content2'          => $data['content2'],
            'content3'          => $data['content3'],
            'add_to_navigation' => $add_to_nav,
            'add_to_footer'     => $add_to_footer,
            'add_primary'       => $add_primary,
            'external_link'     => $link,
            'status'            => $status,
            'slug'              => $data['slug'],
            'meta_title'        => $data['seo_title'],
            'meta_description'  => $data['seo_description'],
            'meta_keywords'     => $data['seo_keywords'],
            'target'            => $target,
            'published_by'      => $published_by,
            'published_date'    => $published_date,
        );

        $this->addEditTable($master_data, $master_tabledata);
        $page_id = $data['page_id'];

        $q_del_3 = "delete from page_moredata where page_id = " . $_POST['page_id'];
        $ins     = $this->dbh->prepare($q_del_3);
        $ins->execute();

        if (isset($data['item_desc']) && is_array($data['item_desc']) && count($data['item_desc']) > 0)
        {
            $extra_tabledata = array(
                "d_table_name" => 'page_moredata'
            );

            for ($i = 0; $i < count($data['item_desc']); $i++)
            {
                if (isset($data['item_desc'][$i]) && $data['item_desc'][$i] != '')
                {
                    $extra_data = array(
                        'page_id'     => $_POST['page_id'],
                        'description' => trim($data['item_desc'][$i])
                    );

                    $this->addEditTable($extra_data, $extra_tabledata);
                }
            }
        }

        $_SESSION['smsg'] = 'Page edited';

        if (isset($data['submit_approval']) && $data['submit_approval'] == '1')
        {
            $this->send_approval('page', $data['page_name']);
        }

        if (isset($data['save_preview']) && $data['save_preview'] == '1')
        {
            $this->show_preview('page', $page_id);
        }
    }

    function add_category($data, $files)
    {

        if ($_FILES['pdf_path']['name'] != '')
        {
            $img2    = preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $_FILES['pdf_path']['name']);
            $mapurl2 = time() . $img2;
            move_uploaded_file($_FILES['pdf_path']['tmp_name'], UPLOAD_PATH . "categories/" . $mapurl2) or die("Could not copy");
        }

        $conds    = array(
            'table_name' => 'categories',
            'field_name' => 'position',
            'conds'      => array('parent_id' => '0')
        );
        $position = $this->getMaxField($conds);

        $master_tabledata = array(
            "d_table_name" => 'categories'
        );
        $master_data      = array(
            'parent_id'        => $data['parent'],
            'image'            => $data['banner_image_name'],
            'pdf_path'         => $mapurl2,
            'title'            => $data['title'],
            'content'          => $data['page_data'],
            'status'           => 1,
            'add_date'         => date('Y-m-d H:i:s'),
            'position'         => $position,
            'slug'             => $data['slug'],
            'meta_title'       => $data['seo_title'],
            'meta_description' => $data['seo_description'],
            'meta_keywords'    => $data['seo_keywords']
        );

        $this->addEditTable($master_data, $master_tabledata);
        $cat_id = $this->last_insert_id;

        $_SESSION['smsg'] = 'Category added';
    }

    function edit_category($data, $files)
    {

        if ($files['pdf_path']['name'] != '')
        {
            $img2    = preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $_FILES['pdf_path']['name']);
            $mapurl2 = time() . $img2;
            move_uploaded_file($_FILES['pdf_path']['tmp_name'], UPLOAD_PATH . "categories/" . $mapurl2) or die("Could not copy");
        }
        else
        {
            $mapurl2 = $data['old_pdf'];
        }

        $master_tabledata = array(
            "d_table_name" => 'categories',
            "d_pk_name"    => 'id'
        );

        $master_data = array(
            'id'               => $data['cat_id'],
            'image'            => $data['banner_image_name'],
            'pdf_path'         => $mapurl2,
            'parent_id'        => $data['parent'],
            'title'            => $data['title'],
            'content'          => $data['page_data'],
            'slug'             => $data['slug'],
            'meta_title'       => $data['seo_title'],
            'meta_description' => $data['seo_description'],
            'meta_keywords'    => $data['seo_keywords']
        );

        $this->addEditTable($master_data, $master_tabledata);
        $cat_id = $data['cat_id'];

        $_SESSION['smsg'] = 'Category edited';
    }

    function add_brand($data, $files)
    {

        if ($_FILES['logo_image']['name'] != '')
        {
            $img1   = preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $_FILES['logo_image']['name']);
            $mapurl = time() . $img1;
            move_uploaded_file($_FILES['logo_image']['tmp_name'], UPLOAD_PATH . "brands/" . $mapurl) or die("Could not copy");
        }
        else
        {
            $mapurl = '';
        }

        $master_tabledata = array(
            "d_table_name" => 'brands'
        );
        $master_data      = array(
            'title'      => $data['title'],
            'logo_image' => $mapurl,
            'slug'       => $data['slug'],
            'add_date'   => date('Y-m-d H:i:s')
        );

        $this->addEditTable($master_data, $master_tabledata);
        $page_id = $this->last_insert_id;

        $_SESSION['smsg'] = 'Brand added';
    }

    function edit_brand($data, $files)
    {
        if ($files['logo_image']['name'] != '')
        {
            $img1   = preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $_FILES['logo_image']['name']);
            $mapurl = time() . $img1;
            move_uploaded_file($_FILES['logo_image']['tmp_name'], UPLOAD_PATH . "brands/" . $mapurl) or die("Could not copy");
        }
        else
        {
            $mapurl = $data['old_image'];
        }

        $master_tabledata = array(
            "d_table_name" => 'brands',
            "d_pk_name"    => 'id'
        );

        $master_data = array(
            'id'         => $data['id'],
            'logo_image' => $mapurl,
            'title'      => $data['title'],
            'slug'       => $data['slug']
        );

        $this->addEditTable($master_data, $master_tabledata);
        $page_id = $data['id'];

        $_SESSION['smsg'] = 'Brand edited';
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' kB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    function png2jpg($filePath, $outputFile, $quality)
    {
        $image = imagecreatefrompng($filePath);
        $bg    = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, TRUE);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        imagejpeg($bg, $outputFile, $quality);
        imagedestroy($bg);
        unlink($filePath);
    }

    function mime_type($file)
    {
        $mime_type = array(
            "3dml"        => "text/vnd.in3d.3dml",
            "3g2"         => "video/3gpp2",
            "3gp"         => "video/3gpp",
            "7z"          => "application/x-7z-compressed",
            "aab"         => "application/x-authorware-bin",
            "aac"         => "audio/x-aac",
            "aam"         => "application/x-authorware-map",
            "aas"         => "application/x-authorware-seg",
            "abw"         => "application/x-abiword",
            "ac"          => "application/pkix-attr-cert",
            "acc"         => "application/vnd.americandynamics.acc",
            "ace"         => "application/x-ace-compressed",
            "acu"         => "application/vnd.acucobol",
            "adp"         => "audio/adpcm",
            "aep"         => "application/vnd.audiograph",
            "afp"         => "application/vnd.ibm.modcap",
            "ahead"       => "application/vnd.ahead.space",
            "ai"          => "application/postscript",
            "aif"         => "audio/x-aiff",
            "air"         => "application/vnd.adobe.air-application-installer-package+zip",
            "ait"         => "application/vnd.dvb.ait",
            "ami"         => "application/vnd.amiga.ami",
            "apk"         => "application/vnd.android.package-archive",
            "application" => "application/x-ms-application",
            "jpg"         => "image/jpeg",
            "jpeg"        => "image/jpeg",
            "png"         => "image/png",
            "gif"         => "image/gif"
        );

        $extension = \strtolower(\pathinfo($file, \PATHINFO_EXTENSION));

        if (isset($mime_type[$extension]))
        {
            return $mime_type[$extension];
        }
        else
        {
            //   throw new \Exception("Unknown file type");
        }
    }

    function escapeJavaScriptText($string)
    {
        return str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string) $string), "\0..\37'\\")));
    }

    function advance_filter_options($table_name, $conds = array(), $field = 'title')
    {
        if (strpos($field, ' as ') !== false)
        {
            $this->selected_fields[] = $field;
            $ar                      = explode(" ", $field);
            $field                   = end($ar);
        }

        $data                  = $this->getTable2($table_name, $conds);
        $this->selected_fields = array('*');
        $arr                   = array();

        foreach ($data as $d)
        {
            $arr[$d['id']] = $d[$field];
        }

        return $arr;
    }

    function add_career($data, $files)
    {
        $status        = '0';
        $job_category  = 'F';
        $department_id = 0;
        $deadline      = "";

        $slug = $this->slugify($data['title']);

        if (strlen($slug) > 80)
        {
            $slug = substr(strip_tags($slug), 0, 80);
            $slug = $slug . '-' . $data['job_id'];
        }
        if (!$this->checkSlugExist('careers_career', $slug))
        {
            $slug = $slug . "-" . rand(0, 10);
        }


        if (isset($data['submit']) && $data['submit'] == '1')
        {
            $status = '1';
        }
        if (isset($data['job_category']) && $data['job_category'] != '' && $data['type'] == 'S')
        {
            $job_category = $data['job_category'];
        }

        if (isset($data['department_id']) && $data['department_id'] != '')
        {
            $department_id = $data['department_id'];
        }

        if (isset($data['application_deadline']) && $data['application_deadline'] != '')
        {
            $deadline = date('Y-m-d', strtotime($data['application_deadline']));
        }

        if (isset($data['emails']) && $data['emails'] != '')
        {
            $data['emails'] = $this->email_validation($data['emails']);
        }


        $data['title']         = strip_tags($data['title']);
        $data['position']      = strip_tags($data['position']);
        $data['meta_title']    = strip_tags($data['meta_title']);
        $data['meta_keywords'] = strip_tags($data['meta_keywords']);

        $data['description'] = str_replace('<script>', '', $data['description']);
        $data['description'] = str_replace('</script>', '', $data['description']);

        $tabledata = array(
            "d_table_name" => 'careers_career'
        );

        $_data = array(
            'type'                 => $data['type'],
            'title'                => $this->filter_string($data['title']),
            'job_id'               => $data['job_id'],
            'job_type'             => $data['job_type'],
            'publish_date'         => date('Y-m-d', strtotime($data['publish_date'])),
            'application_deadline' => $deadline,
            'no_of_position'       => $data['no_of_position'],
            'position'             => $this->filter_string($data['position']),
            'department_id'        => $department_id,
            'school_id'            => $data['school_id'],
            'description'          => trim($data['description']),
            'emails'               => $data['emails'],
            'job_category'         => $job_category,
            'slug'                 => $slug,
            'status'               => $status,
            'meta_title'           => $this->filter_string($data['meta_title']),
            'meta_keywords'        => $data['meta_keywords'],
            'meta_description'     => $data['meta_description'],
            'add_date'             => CURRDATE
        );

        $this->addEditTable($_data, $tabledata);
        $career_id = $this->last_insert_id;
    }

    function edit_career($data, $files)
    {
        $status        = '0';
        $job_category  = 'F';
        $department_id = 0;
        $deadline      = "";

        $slug_data = array();
        $slug_id   = $_POST['id'];
        $slug_data = $this->get_slug($slug_id, $table     = 'careers_career');
        $slug      = $slug_data['slug'];

        if (isset($data['submit']) && $data['submit'] == '1')
        {
            $status = '1';
        }
        if (isset($data['job_category']) && $data['job_category'] != '' && $data['type'] == 'S')
        {
            $job_category = $data['job_category'];
        }
        if (isset($data['department_id']) && $data['department_id'] != '')
        {
            $department_id = $data['department_id'];
        }

        if (isset($data['application_deadline']) && $data['application_deadline'] != '')
        {
            $deadline = date('Y-m-d', strtotime($data['application_deadline']));
        }

        if (isset($data['emails']) && $data['emails'] != '')
        {
            $data['emails'] = $this->email_validation($data['emails']);
        }


        $data['title']         = strip_tags($data['title']);
        $data['position']      = strip_tags($data['position']);
        $data['meta_title']    = strip_tags($data['meta_title']);
        $data['meta_keywords'] = strip_tags($data['meta_keywords']);

        $data['description'] = str_replace('<script>', '', $data['description']);
        $data['description'] = str_replace('</script>', '', $data['description']);

        $tabledata = array(
            "d_table_name" => 'careers_career',
            "d_pk_name"    => 'id'
        );

        $_data = array(
            'type'                 => $data['type'],
            'title'                => $this->filter_string($data['title']),
            'job_id'               => $data['job_id'],
            'job_type'             => $data['job_type'],
            'publish_date'         => date('Y-m-d', strtotime($data['publish_date'])),
            'application_deadline' => $deadline,
            'no_of_position'       => $data['no_of_position'],
            'position'             => $this->filter_string($data['position']),
            'department_id'        => $department_id,
            'school_id'            => $data['school_id'],
            'description'          => trim($data['description']),
            'job_category'         => $job_category,
            'emails'               => $data['emails'],
            'slug'                 => $slug,
            'status'               => $status,
            'meta_title'           => $this->filter_string($data['meta_title']),
            'meta_keywords'        => $this->filter_string($data['meta_keywords']),
            'meta_description'     => $data['meta_description'],
            'add_date'             => CURRDATE,
            'id'                   => $data['id']
        );

        $this->addEditTable($_data, $tabledata);
    }

    function get_team($conds = array(), $single = false)
    {
        global $dbh;

        global $advance_filters;
        if ($advance_filters && count($advance_filters) > 0)
        {
            if (isset($_GET) && count($_GET) > 0)
            {
                foreach ($_GET as $key => $value)
                {
                    if ($key == 'a_tc_center_id')
                    {
                        $key = 'a_tc.center_id';
                    }
                    if (substr($key, 0, 2) == 'a_')
                    {
                        $conds[str_replace('a_', '', $key)] = $value;
                    }
                    if (substr($key, 0, 10) == 'date_from_')
                    {
                        $dates = explode('-', $value);
                        $dt    = explode('/', trim($dates[0]));
                        $fdate = $dt[2] . '-' . $dt[1] . '-' . $dt[0];

                        $dt1        = explode('/', trim($dates[1]));
                        $tdate      = $dt1[2] . '-' . $dt1[1] . '-' . $dt1[0];
                        $this->hqry .= " and `" . str_replace('date_from_', '', $key) . "` between '" . $fdate . "' and '" . $tdate . "' ";
                    }
                }
            }
        }

        $limitsql = "";
        $nqry     = "";
        $mqry     = '';
        $qrydata  = array();
        $i        = 1;
        foreach ($conds as $key => $val)
        {
            $mqry                 .= " and " . $key . " = :bind" . $i . " ";
            $qrydata["bind" . $i] = $val;
            $i++;
        }

        $mqry .= $this->middle_queries();

        $qry = "SELECT SQL_CALC_FOUND_ROWS te.*,ce.id as cid
                FROM `team` te
                LEFT JOIN `team_centers` tc ON (tc.team_id = team_id)
                LEFT JOIN `centers` ce ON (ce.id = tc.center_id) WHERE 1=1
               $mqry";

        //echo $qry;

        $stmt            = $this->dbh->prepare($qry);
        $stmt->execute($qrydata);
        $res             = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt            = $this->dbh->query('SELECT FOUND_ROWS() as cnt');
        $count           = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->totalrows = $count['cnt'];
        return $res;
    }

    function show_preview($type, $id)
    {
        if ($type == 'page')
        {
            $dataq     = "Select * from pages where id = " . $id;
            $qry       = $this->dbh->prepare($dataq);
            $qry->execute();
            $page_info = $qry->fetch(PDO::FETCH_ASSOC);

            $location1 = ROOT_URL . $page_info['slug'];

            if (isset($page_info) && isset($page_info['parent_id']) && $page_info['parent_id'] > 0)
            {
                $dataq1     = "Select * from pages where id = " . $page_info['parent_id'];
                $qry1       = $this->dbh->prepare($dataq1);
                $qry1->execute();
                $page_info1 = $qry1->fetch(PDO::FETCH_ASSOC);

                if (strtolower($page_info1['page'] == 'home'))
                {
                    $location1 = ROOT_URL;
                }
                else
                {
                    $location1 = ROOT_URL . $page_info1['slug'] . '/' . $page_info['slug'];
                }
            }

            $location  = $location1;
            $location2 = 'add_edit_page.php?page_id=' . $id . '&edit=1';
        }
        else if ($type == 'news' || $type == 'event')
        {
            $dataq = "Select * from news_events where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            $location  = ROOT_URL . $type . '/' . $info['slug'];
            $location2 = 'add_edit_' . $type . '.php?id=' . $id . '&edit=1';
        }
        else if ($type == 'project')
        {
            $dataq = "Select * from projects where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            $location  = ROOT_URL . 'project/' . $info['slug'];
            $location2 = 'add_edit_project.php?id=' . $id . '&edit=1';
        }
        else if ($type == 'resource')
        {
            $dataq = "Select * from resources where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            $location  = ROOT_URL . 'crdf-resources/' . $info['slug'];
            $location2 = 'add_edit_resource.php?id=' . $id . '&edit=1';
        }
        else if ($type == 'team')
        {
            $dataq = "Select * from team where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            $location  = ROOT_URL . 'people/' . $info['slug'];
            $location2 = 'add_edit_member.php?id=' . $id . '&edit=1';
        }
        else if ($type == 'center')
        {
            $dataq = "Select * from centers where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            $location  = ROOT_URL . 'center/' . $info['slug'];
            $location2 = 'add_edit_center.php?id=' . $id . '&edit=1';
        }
        else if ($type == 'career')
        {
            $dataq = "Select * from careers where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            $location  = ROOT_URL . 'work-with-us/' . $info['slug'];
            $location2 = 'add_edit_career.php?id=' . $id . '&edit=1';
        }
        else if ($type == 'banner')
        {
            $dataq = "Select * from home_banners where id = " . $id;
            $qry   = $this->dbh->prepare($dataq);
            $qry->execute();
            $info  = $qry->fetch(PDO::FETCH_ASSOC);

            if ($info['type'] == 'C')
            {
                $dataq1   = "Select * from centers where id = " . $info['center_id'];
                $qry      = $this->dbh->prepare($dataq1);
                $qry->execute();
                $info2    = $qry->fetch(PDO::FETCH_ASSOC);
                $location = ROOT_URL . 'center/' . $info2['slug'];
            }
            else
            {
                $location = ROOT_URL;
            }

            $location2 = 'add_edit_banner.php?id=' . $id . '&edit=1';
        }
        ?>
        <script type="text/javascript">
            newWindow = window.open("", "_blank");
            //newWindow.location = 'add_edit_news.php?id=<?= $id ?>&edit=1';
            newWindow.location = '<?= $location2 ?>';
            window.open("", "_self");
        </script>
        <script type="text/javascript">
            newWindow = window.open("", "_blank");
            //newWindow.location = 'add_edit_news.php?id=<?= $id ?>&edit=1';
            newWindow.location = '<?= $location ?>';
            window.close();
        </script>
        <?php
        ob_flush();
    }

}
?>
