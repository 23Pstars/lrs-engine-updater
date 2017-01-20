<?php
/**
 * LRsoft Corp.
 * http://lrsoft.co.id
 *
 * Author: Zaf
 */

// credentials
$username = 'user';
$password = 'pass';

$root_path = 'lrs-engine';
$available_branch = array(
    'engine' => 'master',
    'modules' => array(
        'db-exporter', 'mailer', 'nationality',
        'currency', 'fastboat', 'tour', 'flight'
    )
);

// fetch domain lists
$domain_lists = array();
$list_repository = 'https://bitbucket.org/lrsoft/lrs-engine-domains/raw/master/README.md';
$domain_lists_raw = file_get_contents( $list_repository, false, stream_context_create(array(
    'http' => array(
        'header'  => "Authorization: Basic " . base64_encode( "$username:$password" )
    )
) ) );
foreach (explode(PHP_EOL, $domain_lists_raw) as $line)
    if (substr($line, 0, 1) == '-')
        $domain_lists[] = trim(substr($line, 1));

?>

<html>
<head>
    <title>LRS Engine | LRsoft Corp.</title>
    <style>
        .parent {
            display: flex;
        }

        .parent > div {
            flex: 1;
        }

        .update .containers {
            margin: 5px 0 10px;
        }

        .update .containers .update-progress {
            color: red;
        }
        .update .containers .update-result {
            color: green;
        }
    </style>
</head>

<body>

<div class="parent">
    <div class="install">
        <h2>Installer</h2>
        <?php if( isset( $_REQUEST[ 'do-install' ] ) && $_REQUEST[ 'do-install' ] == 'do-install' ) :

            $zip = new ZipArchive();
            $download_base_url = 'https://bitbucket.org/lrsoft/lrs-engine/get/';

            !isset( $_REQUEST[ 'root_path' ] ) || $root_path = $_REQUEST[ 'root_path' ];
            $module_path = $root_path . '/module';
            $temp_path = '__temp';

            $branch_to_install = array();
            $_file_postfix = '.zip';
            $_module_prefix = 'module-';
            $_install_engine = $_install_modules = false;

            if( !empty( $_REQUEST[ 'engine' ] ) ) {
                $_install_engine = true;
                $branch_to_install[ $available_branch[ 'engine' ] ] = $available_branch[ 'engine' ] . $_file_postfix;
            }
            if( !empty( $_REQUEST[ 'modules' ] ) ) {
                $_install_modules = true;
                foreach( $_REQUEST[ 'modules' ] as $module )
                    !in_array( $module, $available_branch[ 'modules' ] ) || $branch_to_install[ $module ] = $_module_prefix . $module . $_file_postfix;
            }

            $chs = array(); $cmh = curl_multi_init();
            foreach( $branch_to_install as $branch => $filename ) {
                $chs[ $branch ] = curl_init();
                curl_setopt_array( $chs[ $branch ], array(
                    CURLOPT_HTTPAUTH            => CURLAUTH_BASIC,
                    CURLOPT_USERPWD             => $username . ':' . $password,
                    CURLOPT_FILE                => fopen( $filename, 'w' ),
                    CURLOPT_URL                 => $download_base_url . $filename
                ) );
                curl_multi_add_handle( $cmh, $chs[ $branch ] );
            }

            $running = null;
            do { curl_multi_exec($cmh, $running); } while ( $running > 0 );

            foreach( $branch_to_install as $branch => $filename ) {
                curl_multi_remove_handle( $cmh, $chs[ $branch ] );
                curl_close( $chs[ $branch ] );
            }

            curl_multi_close( $cmh );

            foreach( $branch_to_install as $branch => $filename ) {
                if( $zip->open( $filename ) == TRUE ) {
                    $first_directory_name = $zip->getNameIndex( 0 );
                    $zip->extractTo( $temp_path );
                    if( $branch == $available_branch[ 'engine' ] ) {
                        if( !file_exists( $root_path ) ) mkdir( $root_path );
                        rename( $temp_path . '/' . $first_directory_name, $root_path );
                    } else {
                        if( !file_exists( $module_path . '/' . $branch ) ) mkdir( $module_path . '/' . $branch, 0775, true );
                        rename( $temp_path . '/' . $first_directory_name . '/module/' . $branch, $module_path . '/' . $branch );
                        rmdir( $temp_path . '/' . $first_directory_name . '/module' ); rmdir( $temp_path . '/' . $first_directory_name );
                    }
                    $zip->close();
                    unlink( $filename );
                    echo $branch . ' extracted<br/>';
                }
            }

            rmdir( $temp_path );
            $branch_version = 'versi-2.1.0';
            $version_file = 'VERSION.md';
            $new_version_contents = '';
            $new_version_contents = '#branch : ' . $branch_version . PHP_EOL;
            $new_version_contents .= '#last-update : ' . date( 'Y-m-d H:i:s' );;
            echo $version_file . ' updated' . ( file_put_contents( $root_path . '/' . $version_file, $new_version_contents ) ? '' : ' fail' ) . '.<br/>';
        else : ?>
            <form action="" method="get">
                <strong>Path</strong><br/>
                <input type="text" name="root_path" value="<?php echo $root_path; ?>" />
                <br/><br/>
                <strong>Core</strong><br/>
                <label><input type="checkbox" name="engine" value="<?php echo $available_branch[ 'engine' ]; ?>"> Engine</label>
                <br/><br/>
                <strong>Modules</strong><br/>
                <?php foreach( $available_branch[ 'modules' ] as $module ) : ?>
                    <label><input type="checkbox" name="modules[]" value="<?php echo $module; ?>"> <?php echo $module; ?></label><br/>
                <?php endforeach; ?>
                <input type="hidden" name="do-install" value="do-install">
                <br/>
                <button>&mdash; Install! &mdash;</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="update">
        <h2>Updater</h2>
        <?php foreach ($domain_lists as $i => $domain) : ?>
            <a href="#<?php echo $i; ?>" class="btn-update" data-id="<?php echo $i; ?>"
               data-domain="<?php echo $domain; ?>">
                Update
            </a>
            &mdash; <?php echo $domain; ?>
            <div class="containers container-<?php echo $i; ?>"></div>
        <?php endforeach; ?>
        <button class="btn-update-all">&mdash; Update all &mdash;</button>
        <input type="hidden" id="username" value="<?php echo urlencode($username); ?>"/>
        <input type="hidden" id="password" value="<?php echo urlencode($password); ?>"/>
    </div>
</div>

<script type="text/javascript">
    (function () {

        var username = document.getElementById('username').value;
        var password = document.getElementById('password').value;

        var btn_update = document.getElementsByClassName('btn-update');
        for (var i = 0; i < btn_update.length; i++)
            btn_update[i].addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var data_id = this.getAttribute('data-id');
                var update_url = this.getAttribute('data-domain') + '/update.php?u=' + username + '&p=' + password;
                var update_container = document.getElementsByClassName('container-' + data_id)[0];
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    update_container.innerHTML = this.readyState == 4 && this.status == 200 ?
                        '<span class="update-result"><strong>Results</strong><br/>' + this.responseText+'</span>' : '<span class="update-progress">Updating... please wait!</span>';
                };
                xhttp.open("GET", update_url, true);
                xhttp.send();

            });
        document.getElementsByClassName('btn-update-all')[0].addEventListener('click',function(){
            for (var i = 0; i < btn_update.length; i++)
                btn_update[i].click();
        });
    })();

</script>

</body>

</html>
