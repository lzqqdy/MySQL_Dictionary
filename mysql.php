<?php
//访问方法
// /mysql.php?pwd=root&name=数据库名为空则读取配置文件中的数据库名&table=表名为空所有表
/**
 * pwd      访问密码
 * name     数据库名称
 * table    表名
 */
session_start();
header("Content-type:text/html;charset=utf-8");

//$dbname = isset($_GET['name']) ? $dbname = $_GET['name'] : $dbname = null;
$dbname = $_GET['name'] ?? null; //php>7.0,否则使用上面的
// 配置数据库
$database = [];
$password = 'root';                 //访问密码GET['pwd']
$database['DB_HOST'] = '127.0.0.1'; //数据库地址
$database['DB_NAME'] = 'fastadmin'; //数据库名称
$database['DB_USER'] = 'root';      //用户名
$database['DB_PWD'] = 'root';      //密码
$char_set = 'UTF8';                 //数据库编码
//isset($dbname) ? $dbname : $dbname = $database['DB_NAME'];
$dbname = $dbname ?? $database['DB_NAME']; //php>7.0
$pwd = !empty($_GET['pwd']) ? $_GET['pwd'] : $_SESSION['pwd'];
if ($password != $pwd) {
    session_destroy();
    exit('无权访问');
}
$_SESSION['pwd'] = $pwd;
//连接数据库
date_default_timezone_set('Asia/Shanghai');
$mysql_conn = @mysqli_connect("{$database['DB_HOST']}", "{$database['DB_USER']}",
    "{$database['DB_PWD']}") or die("Mysql connect is error.");
mysqli_select_db($mysql_conn, $dbname);
$result = mysqli_query($mysql_conn, 'show tables');
mysqli_query($mysql_conn, 'SET NAMES ' . $char_set);

if (isset($_GET['table'])) {
    $tables[]['TABLE_NAME'] = $_GET['table'];
} else {
    // 取得所有表名
    while ($row = mysqli_fetch_array($result)) {
        $tables[]['TABLE_NAME'] = $row[0];
    }
}
// 循环取得所有表的备注及表中列信息
foreach ($tables as $k => $v) {
    $sql = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.TABLES ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$dbname}'";
    $table_result = mysqli_query($mysql_conn, $sql);
    while ($t = mysqli_fetch_array($table_result)) {
        $tables[$k]['TABLE_COMMENT'] = $t['TABLE_COMMENT'];
    }
    $sql = 'SELECT * FROM ';
    $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
    $sql .= 'WHERE ';
    $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$dbname}'";
    if (isset($_GET['table'])) //表名参数存在才查询出结构
    {
        $fields = array();
        $field_result = mysqli_query($mysql_conn, $sql);
        while ($t = mysqli_fetch_array($field_result)) {
            $fields[] = $t;
        }
        $tables[$k]['COLUMN'] = $fields;
        if (isset($_GET['data']) && $_GET['data'] == 1) //查询数据
        {
            $sql = 'SELECT * FROM ';
            $sql .= "$dbname.{$v['TABLE_NAME']}";
            $result = mysqli_query($mysql_conn, $sql);
            while ($t = mysqli_fetch_assoc($result)) {
                $data[] = $t;
            }
            if (isset($data)) {
                $tables[$k]['DATA'] = $data;
            }
        }
    }
}
mysqli_close($mysql_conn);
//print_r($data);
$html = '';
if (isset($_GET['table']) && !isset($_GET['data'])) {
    $html .= '<h1 style="text-align:center;">表结构</h1>';
    $html .= '<p style="text-align:right;> <a href="javascript:void(0)" class="data" data-tabname="' . $v['TABLE_NAME'] . '" >[查看数据]</a></p>';
    $html .= '<p style="text-align:center;margin:20px auto;">生成时间：' . date('Y-m-d H:i:s') . '</p>';
    // 循环所有表
    foreach ($tables as $k => $v) {
        $html .= '<table border="1" cellspacing="0" cellpadding="0" align="center">';
        $html .= '<caption>' . $v['TABLE_NAME'] . $v['TABLE_COMMENT'] . '</caption>';
        $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th><th>允许非空</th><th>自动递增</th><th>备注</th></tr>';
        $html .= '';
        foreach ($v['COLUMN'] AS $f) {
            $html .= '<td class="c1">' . $f['COLUMN_NAME'] . '</td>';
            $html .= '<td class="c2">' . $f['COLUMN_TYPE'] . '</td>';
            $html .= '<td class="c3">' . $f['COLUMN_DEFAULT'] . '</td>';
            $html .= '<td class="c4">' . $f['IS_NULLABLE'] . '</td>';
            $html .= '<td class="c5">' . ($f['EXTRA'] == 'auto_increment' ? '是' : ' ') . '</td>';
            $html .= '<td class="c6">' . $f['COLUMN_COMMENT'] . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></p>';
        $html .= '<p style="text-align:left;margin:20px auto;">总共：' . count($v['COLUMN']) . '个字段</p>';
        $html .= '</body></html>';
    }
} else {
    if (isset($_GET['data']) && $_GET['data'] == 1) {
        $html .= '<h1 style="text-align:center;">表数据</h1>';
        $html .= '<p style="text-align:right;><a href="javascript:void(0)" class="abiao" data-tabname="' . $v['TABLE_NAME'] . '" >[查看结构]</a></p>';
        //循环数据
        foreach ($tables as $k => $v) {
            $html .= '<table border="1" cellspacing="0" cellpadding="0" align="center">';
            $html .= '<caption>' . $v['TABLE_NAME'] . $v['TABLE_COMMENT'] . '</caption>';
            $html .= '<tbody><tr>';
            foreach ($v['COLUMN'] AS $f) {
                $html .= '<th>' . $f['COLUMN_NAME'] . '</th>';
            }
            $html .= '</tr>';
            $html .= '';
            if (isset($v['DATA'])) {
                foreach ($v['DATA'] AS $kk => $vv) {
                    foreach ($vv as $kkk => $vvv) {
                        $html .= '<td class="cd">' . $vv[$kkk] . '</td>';
                    }
                    $html .= '</tr>';
                }
                $count = count($v['DATA']);
            } else {
                $count = 0;
            }
            $html .= '</tbody></table></p>';
            $html .= '<p style="text-align:left;margin:20px auto;">总共：' . $count . '条数据</p>';
            $html .= '</body></html>';
        }
    } else {
        $html .= '<h1 style="text-align:center;">数据字典</h1>';
        $html .= '<p style="text-align:center;margin:20px auto;">生成时间：' . date('Y-m-d H:i:s') . '</p>';
        foreach ($tables as $k => $v) {
            $html .= '<table border="1" cellspacing="0" cellpadding="0" align="center">';
            $html .= '<caption>表名：' . $v['TABLE_NAME'] . ' ------- ' . $v['TABLE_COMMENT'] .
                '<a href="javascript:void(0)" class="abiao" data-tabname="' . $v['TABLE_NAME'] . '" >[查看结构]</a>
            <a href="javascript:void(0)" class="data" data-tabname="' . $v['TABLE_NAME'] . '" >[查看数据]</a>
            </caption>';
            $html .= '</tbody></table></p>';
        }
        $html .= '<p style="text-align:left;margin:20px auto;">总共：' . count($tables) . '个数据表</p>';
        $html .= '</body></html>';
    }
}
// 输出
echo '<html>
    <meta charset="utf-8">
    <title>' . '</title>
    <style>
        body,td,th {font-family:"思源黑体"; font-size:12px;}
        table,h1,p{width:960px;margin:0px auto;}
        table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;}
        table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
        table th{text-align:left; font-weight:bold;height:26px; line-height:26px; font-size:12px; border:1px solid #CCC;padding-left:5px;}
        table td{height:20px; font-size:14px; border:1px solid #CCC;background-color:#fff;padding-left:5px;}
        .c1{ width: 150px;}.c2{ width: 150px;}.c3{ width: 80px;}.c4{ width: 100px;}.c5{ width: 100px;}.c6{ width: 300px;}
    </style>
    <body>';
echo $html;
?>
<script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="http://apps.bdimg.com/libs/layer/2.1/layer.js"></script>
<script type="text/javascript">
    var geturl = window.location.href;
    var arr = geturl.split('?');
    var url = arr[0];
    var dbname = '<?php echo $dbname;?>';
    $(document).ready(function () {
        $(".abiao").on('click', function (e) {
            var tabname = $(this).data("tabname");
            layer.open({
                title: name,
                area: ['1000px', '600px'],
                offset: '100px',
                type: 2,
                maxmin: true,
                shadeClose: true,
                content: url + '?name=' + dbname + '&table=' + tabname
            });
        })
    });
    $(document).ready(function () {
        $(".data").on('click', function (e) {
            var tabname = $(this).data("tabname");
            layer.open({
                title: name,
                shadeClose: true,
                area: ['95%', '95%'],
                offset: '15px',
                type: 2,
                content: url + '?name=' + dbname + '&table=' + tabname + '&data=1'
            });
        })
    });
    $('body', document).on('keyup', function (e) {
        //console.log("按下esc");
        layer.closeAll();
    });
</script>