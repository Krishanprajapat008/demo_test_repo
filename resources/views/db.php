<?php

class dbclass
{

    var $totalrows;
    var $limit           = 0;
    var $offset          = 0;
    var $orderby         = '';
    var $searchkeyword   = '';
    var $ordertype       = 'asc';
    var $groupby         = '';
    var $havingsearch    = false;
    var $mqry            = "";
    var $hqry            = "";
    var $searchparams    = array();
    var $app_id;
    var $last_insert_id;
    var $api_key         = '7065726e65746c696e6b6e616e';
    var $selected_fields = array('*');

    //function dbclass()
    public function __construct() 
    {
        try
        {
            $this->dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->dbh->query("SET CHARACTER SET 'UTF8'");
            $this->dbh->query("SET SESSION time_zone = '+05:30'");
            $this->dbh->exec("set names utf8");
        }
        catch (PDOException $e)
        {
            echo "Unable to connect to the database. Please try after sometime.";
            echo $e->getMessage();
            exit();
        }

        if (!isset($_POST['user_name']) && !isset($_POST['email']))
        {
            if (!isset($_SESSION['login_check']))
            {
                if (!isset($_GET['e']))
                {
                    $_SESSION['emsg']          = 'Please login';
                    $_SESSION['redirect_link'] = basename($_SERVER['PHP_SELF']);
                    header('location:' . ROOT_URL . 'admin/login.php');
                    echo '<div class="alert-error" style="font-size: 14px;padding: 15px;position: relative;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon-minus-sign"></i><strong>Error!</strong> Your session expired.Please <a href="login.php">click here</a> login </div>';
                    exit;
                }
            }
        }
    }

    function resetFields()
    {
        $this->limit           = 0;
        $this->offset          = 0;
        $this->orderby         = '';
        $this->searchkeyword   = '';
        $this->ordertype       = 'asc';
        $this->groupby         = '';
        $this->havingsearch    = false;
        $this->mqry            = "";
        $this->hqry            = "";
        $this->searchparams    = array();
        $this->last_insert_id  = 0;
        $this->selected_fields = array('*');
    }

    function getSelection()
    {
        if (count($this->selected_fields) == 1 && $this->selected_fields[0] == '*')
        {
            return "this." . $this->selected_fields[0];
        }
        else if (count($this->selected_fields) == 1)
        {
            return $this->selected_fields[0];
        }
        else if (count($this->selected_fields) == 0)
        {
            return 'this.*';
        }
        else
        {
            return implode(',', $this->selected_fields);
        }
    }

    function middle_queries()
    {
        $mqry = $this->mqry;
        $hqry = $this->hqry;
        if (count($this->searchparams) > 0 and $this->searchkeyword != "" and $this->searchkeyword != "Search")
        {
            $searchsql = "";
            for ($i = 0; $i < count($this->searchparams); $i++)
            {
                $searchsql .= $this->searchparams[$i] . " LIKE '%" . trim(addslashes($this->searchkeyword)) . "%' or ";
            }
            if ($this->havingsearch)
            {
                $hqry .= " and (" . substr($searchsql, 0, -3) . " )";
            }
            else
            {
                $mqry .= " and (" . substr($searchsql, 0, -3) . " )";
            }
        }

        if ($this->group_by() != "")
        {
            $mqry .= $this->group_by();
        }
        if (!empty($hqry))
        {
            $mqry .=" Having 1 " . $hqry;
        }
        if ($this->orderby != "")
        {
            $mqry .= " order by " . $this->orderby . " " . $this->ordertype;
        }
        //$limit != "" and
        if ($this->offset != 0)
        {
            $mqry .= " limit $this->limit,$this->offset";
        }
        return $mqry;
    }

    function middle_queries2()
    {
        $mqry = $this->mqry;
        $hqry = $this->hqry;
        if (count($this->searchparams) > 0 and $this->searchkeyword != "" and $this->searchkeyword != "Search")
        {
            $searchsql = "";
            for ($i = 0; $i < count($this->searchparams); $i++)
            {
                $searchsql .= $this->searchparams[$i] . " LIKE '%" . trim(addslashes($this->searchkeyword)) . "%' or ";
            }
            if ($this->havingsearch)
            {
                $hqry .= " and (" . substr($searchsql, 0, -3) . " )";
            }
            else
            {
                $mqry .= " and (" . substr($searchsql, 0, -3) . " )";
            }
        }

        if ($this->group_by() != "")
        {
            $mqry .= $this->group_by();
        }
        if (!empty($hqry))
        {
            $mqry .=" Having 1 " . $hqry;
        }
        //$limit != "" and
        return $mqry;
    }

    function group_by()
    {
        $groupsql = '';
        if ($this->groupby != '')
        {
            $groupsql = " group by " . $this->groupby;
        }
        return $groupsql;
    }

    function addEditTable($data, $tabledata)
    {
        /*
          $tabledata = array(
          "d_table_name"=>'news',
          "d_pk_name"=>'news_id',
          );
         */
        global $dbh;
        $bind = "";
        if (array_key_exists('d_pk_name', $tabledata))
        {

            foreach (array_keys($data) as $val)
            {
                if ($val != $tabledata['d_pk_name'])
                    $bind .= $val . '=:' . $val . ',';
            }
            $params = substr($bind, 0, -1);

            $stmt = $this->dbh->prepare("update $tabledata[d_table_name] set $params  where $tabledata[d_pk_name]=:$tabledata[d_pk_name]");

            $update = $stmt->execute($data);

            if ($update == 1)
                return 1;
            else
                return 0;
        }
        else
        {

            $bind = ':' . implode(',:', array_keys($data));

            $stmt                 = $this->dbh->prepare("insert into $tabledata[d_table_name] (" . implode(',', array_keys($data)) . ") values (" . $bind . ")");
            $event                = $stmt->execute($data);
            $this->last_insert_id = $this->dbh->lastInsertId();
            if ($event == 1)
                return 1;
            else
                return 0;
        }
    }

    function getTable($tablename, $conds = array(), $single = false)
    {
        global $dbh;
        
        global $advance_filters;
        if (@count((array)$advance_filters) > 0)
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

                        $dt1   = explode('/', trim($dates[1]));
                        $tdate = $dt1[2] . '-' . $dt1[1] . '-' . $dt1[0];
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
            $mqry .= " and " . $key . " = :bind" . $i . " ";
            $qrydata["bind" . $i] = $val;
            $i++;
        }

        $mqry.= $this->middle_queries();
        $sql = "select SQL_CALC_FOUND_ROWS " . $this->getSelection() . "
				from $tablename this 
				where 1 $mqry";

        //echo $sql;
        //exit;

        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($qrydata);



        if ($single)
        {
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        else
        {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt            = $this->dbh->query('SELECT FOUND_ROWS() as cnt');
        $count           = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->totalrows = $count['cnt'];
        return $res;
    }

    function getTable2($tablename, $conds = array(), $single = false)
    {
        global $dbh;


        $limitsql = "";
        $nqry     = "";
        $mqry     = '';
        $qrydata  = array();
        $i        = 1;
        foreach ($conds as $key => $val)
        {
            $mqry .= " and " . $key . " = :bind" . $i . " ";
            $qrydata["bind" . $i] = $val;
            $i++;
        }

        $mqry.= $this->middle_queries();
        $sql = "select SQL_CALC_FOUND_ROWS " . $this->getSelection() . "
				from $tablename this 
				where 1 $mqry";



        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($qrydata);



        if ($single)
        {
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        else
        {
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt            = $this->dbh->query('SELECT FOUND_ROWS() as cnt');
        $count           = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->totalrows = $count['cnt'];
        return $res;
    }
    
//    function get_arrays()
//    {
//        //$arr = array('id','career_id','first_name','email','phone','current_location','highest_qualification','dob','add_date');
//        
//        global $dbh;        
//        $qry       = "select first_name,career_id,email,phone,current_location,highest_qualification,dob,add_date from careers_staff_form where status = '1'";
//        $data = $this->dbh->prepare($qry);
//        $data->execute(array());
//
//        if ($data->rowCount() > 0)
//        {
//            return $menu_items = $data->fetchAll(PDO::FETCH_ASSOC);
//            
//        }
//        else
//        {
//            return 0;
//        }
//    }

    function deleteRecord($data)
    {
        /*
          $data = array(
          "d_table_name"=>'news',
          "d_pk_name"=>'news_id',
          "d_pk_value"=>$_REQUEST['news_id'],
          );
         */
        global $dbh;
        $stmt = $this->dbh->prepare("delete from $data[d_table_name] where $data[d_pk_name]=:d_pk_value", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

        unset($data['d_table_name'], $data['d_pk_name']);

        $deleted = $stmt->execute($data);
        if ($deleted == 1)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    function changeRecord($data)
    {
        /*
          $data = array(
          "d_table_name"=>'news',
          "d_pk_name"=>'news_id',
          "d_pk_value"=>$_REQUEST['news_id'],
          "d_field_name"=>'news_status',
          "d_field_value"=>$_REQUEST['status'],
          ); */

        global $dbh;
        $stmt = $this->dbh->prepare("update $data[d_table_name] set $data[d_field_name]=:d_field_value
							   where $data[d_pk_name]=:d_pk_value", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

        unset($data['d_table_name'], $data['d_field_name'], $data['d_pk_name']);
        $deleted = $stmt->execute($data);
        if ($deleted == 1)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    function updatePosition($table, $field, $pk, $pk_value, $oldorder, $neworder, $parent_name = '', $parent_value = '')
    {
        global $dbh;
        $mdlquery = "";
        if ($parent_name != '' && $parent_value != '')
        {
            $mdlquery = "  and `$parent_name` = " . $parent_value;
        }

        if ($oldorder > $neworder)
        {
            $stmt   = $this->dbh->prepare("update $table set $field = $field + 1 where $field between " . $neworder . " and " . ($oldorder - 1) . $mdlquery);
            $update = $stmt->execute();
        }
        else if ($oldorder < $neworder)
        {
            $stmt   = $this->dbh->prepare("update $table set $field = $field-1 where $field between " . ($oldorder + 1) . " and " . $neworder . $mdlquery);
            $update = $stmt->execute();
        }

        $stmt1   = $this->dbh->prepare("update $table set $field =" . $neworder . " where $pk=" . $pk_value);
        $update1 = $stmt1->execute();
    }
    
    function updatePositionNew($table, $field, $pk, $pk_value, $oldorder, $neworder, $parent_name = '', $parent_value = '')
    {
        global $dbh;
        

        $stmt1   = $this->dbh->prepare("update $table set $field =" . $neworder . " where $pk=" . $pk_value);
        $update1 = $stmt1->execute();
    }
    
    function setCategory($id)
    {
        global $dbh;
        $mdlquery = "";
        $qry        = "select * from categories where cat_master_id=" . $id;
        $data      = $this->fetchQuery($qry);
     
        echo json_encode($data);
    }
    
    function setPosition($id, $position, $value, $table)
    {
        global $dbh;
        $mdlquery = "";
        

        
        if($this->real_escape_string($id))     
        {
            $stmt1   = $this->dbh->prepare("update $table set position =" . $value . " where id=" . $id);
            $update1 = $stmt1->execute();
        }
        else{
            echo "failed";
        }
        
    }
    
    
    
            
    function getDepartment_old($id)
    {
        global $dbh;

        $qry        = $this->dbh->prepare("SELECT * from careers_categories where cat_master_id=" . $id . " order by title asc");
        $qry->execute();

        if ($qry->rowCount() > 0)
        {
            $menu_items = $qry->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($menu_items);
        }
        else
        {
            echo 0;
        }
    }
    
    function getDepartment($id)
    {
        global $dbh;        
        $qry       = "select * from careers_categories where `cat_master_id` = :id order by title asc";
        $data = $this->dbh->prepare($qry);
        $data->execute(array(':id' => $id));

        if ($data->rowCount() > 0)
        {
            $menu_items = $data->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($menu_items);
        }
        else
        {
            echo 0;
        }
    }
   
    
    function getStatus($status, $id)
    {
        global $dbh;
        $mdlquery = "";
        
        if($status == '1') {
            $status_id = '0';
        }
        else if($status == '0') {
            $status_id = '1';
        }
        else{
            echo 0 ; exit;
        }

        $stmt1   = $this->dbh->prepare("update careers_career set status = " . "'$status_id'" . " where id = " . $id);
        $update1 = $stmt1->execute();
    }
 
    
        function get_settings()
        {
            $qry                = "select * from careers_settings";
            return $data        = $this->fetchQuery($qry);
        }

}

?>