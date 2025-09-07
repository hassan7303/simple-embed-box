<?php
/**
 * Plugin Name: Simple Embed Box
 * Description: مدیریت و نمایش Embedها با شورتکد و پیش نمایش ریسپانسیو بدون CSS اضافی.
 * Version: 1.0
 * Author: hassan ali askari
 */

add_action('wp_head', function(){
    echo '<style>
         .seb-embed {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* نسبت 16:9 */
            height: 210px;
            overflow: hidden;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0px 0px 9px 1px rgba(0,0,0,0.3);
            margin: 10px auto;
        }
        .seb-embed iframe { 
            position: absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            border:0;
        } 
        @media (min-width: 1024px) {
           .seb-embed {
                display: block !important;
                margin: 5px auto !important;
                width: 700px !important;
                height: 750px !important; /* ارتفاع ثابت */
                max-height: 397px !important;
                background-color: #222222;
                border-radius: 12px;
                box-shadow: 0px 0px 9px 1px rgba(0,0,0,0.3);
                overflow: hidden; /* جلوگیری از overflow iframe */
                padding: 0; /* padding حذف شد */
            }
        
            .seb-embed iframe {
                width: 100% !important;
                height: 100% !important; /* ارتفاع پر div */
                display: block !important;
                border-radius: 12px; /* همسان با div */
            }
        }
    </style>';
});

// --- شورتکد
function seb_shortcode($atts) {
    $a = shortcode_atts(array('id' => ''), $atts);
    if(!$a['id']) return '';

    $embeds = get_option('seb_embeds', []);
    if(isset($embeds[$a['id']])) {
        $iframe = preg_replace('/^.*?(\<iframe.*<\/iframe>).*$/s', '$1', $embeds[$a['id']]);
        return '<div class="seb-embed">'.$iframe.'</div>';
    }
    return '';
}
add_shortcode('embedbox', 'seb_shortcode');

add_action('admin_menu', function(){
    add_menu_page(
        'Embed Box',
        'Embed Box',
        'manage_options',
        'seb-embed-box',
        'seb_admin_page'
    );
});

add_action('admin_post_seb_delete', function(){
    if(!current_user_can('manage_options')) return;

    check_admin_referer('seb_delete_nonce');

    if(isset($_POST['seb_delete_id'])) {
        $embeds = get_option('seb_embeds', []);
        $id = sanitize_text_field($_POST['seb_delete_id']);
        if(isset($embeds[$id])) {
            unset($embeds[$id]);
            update_option('seb_embeds', $embeds);
            wp_redirect(admin_url('admin.php?page=seb-embed-box&deleted=1'));
            exit;
        }
    }
});

function seb_admin_page() {
    if(isset($_POST['seb_code'])) {
        $embeds = get_option('seb_embeds', []);
        $id = uniqid();
        $code = wp_kses_post($_POST['seb_code']);
        $code = preg_replace('/<style.*?<\/style>/is', '', $code);
        $code = preg_replace('/class=".*?"/i','',$code);
        $embeds[$id] = $code;
        update_option('seb_embeds', $embeds);
        echo "<div class='updated'><p>ذخیره شد ✅ شورتکد شما: <code>[embedbox id=\"$id\"]</code></p></div>";
    }

    if(isset($_GET['deleted'])) {
        echo "<div class='updated'><p>شورتکد حذف شد ✅</p></div>";
    }

    ?>
    <div class="wrap">
        <h1>مدیریت Embedها</h1>
        <h2>افزودن Embed جدید</h2>
        <form method="post">
            <textarea name="seb_code" rows="5" style="width:100%;"></textarea>
            <p><input type="submit" class="button button-primary" value="ذخیره"></p>
        </form>

        <h2>Embedهای ذخیره شده</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>پیش‌نمایش ویدیو</th>
                    <th>شورتکد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $embeds = get_option('seb_embeds', []);
            if($embeds) {
                foreach($embeds as $id => $code) {
                    $preview_code = preg_replace('/^.*?(\<iframe.*<\/iframe>).*$/s', '$1', $code);

                    echo '<tr>';
                    echo '<td><div class="seb-embed">'.$preview_code.'</div></td>';
                    echo '<td><code>[embedbox id="'.$id.'"]</code></td>';
                    echo '<td>
                        <form method="post" style="display:inline;" action="'.admin_url('admin-post.php').'">
                            '.wp_nonce_field('seb_delete_nonce','_wpnonce',true,false).'
                            <input type="hidden" name="seb_delete_id" value="'.$id.'">
                            <input type="hidden" name="action" value="seb_delete">
                            <input type="submit" class="button button-danger" value="حذف">
                        </form>
                    </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3">هیچ Embed ذخیره نشده است</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}

add_action('wp_footer', function () {
    ?>
    <style>
    .wp-video {
        position: relative;
        width: 700px !important;
        max-width: 100% !important;
        margin: 15px auto;
        background: #222;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0px 0px 9px 1px rgba(0,0,0,0.3);
    }
    .wp-video::before {
        content: "";
        display: block;
        padding-top: 56.25%; /* نسبت 16:9 */
    }
    .wp-video video,
    .wp-video .mejs-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100% !important;
    }

   .mejs-overlay{
        width: 700px !important;
        height: 400px !important;
    }
    @media (max-width: 768px) {
        .mejs-overlay{
            width: 370px !important;
            height: 196px !important;
        }
    }
        /* استایل برای وسط‌چین شدن ویدیو */
        /*.elementor-widget-container .wp-video,*/
        /*.category video {*/
        /*    display: block !important;*/
        /*    margin: 0 auto !important;*/
        /*    max-width: 100% !important;*/
        /*    height: auto !important;*/
        /*    width: 700px !important;*/
        /*    background-color: #222222;*/
        /*    padding: 4px;*/
        /*    border-radius: 12px;*/
        /*    box-shadow: 0px 0px 9px 1px;*/
        /*    margin: 5px auto !important;*/
        /*}*/
        /*.mejs-overlay{*/
        /*    width: 700px !important;*/
        /*}*/
        /*  @media (max-width: 768px) {*/
        /*    .elementor-widget-container .wp-video,*/
        /*    .category video {*/
        /*        width: 100% !important;*/
        /*        max-width: 100% !important;*/
        /*        margin: 10px auto !important;*/
        /*    }*/
        /*    .mejs-overlay {*/
        /*        width: 380px !important;*/
        /*        height: 300px !important;*/
        /*    }*/
        /*    .wp-video-shortcode{*/
        /*        height: 290px !important;*/
        /*    }*/
        /*    .video-centered {*/
        /*        width: 400px;*/
        /*        height: 300px !important;*/
        /*        min-width: 185px;*/
        /*    }*/
        /*}*/
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("video").forEach(function(video){
            let link = video.querySelector("a[href]");
            if(link){
                let src = link.getAttribute("href");
                if(!video.querySelector("source")){
                    let source = document.createElement("source");
                    source.src = src;
                    source.type = "video/mp4";
                    video.appendChild(source);
                }
                link.remove();
            }
            // یه کلاس هم اضافه کنیم برای وسط‌چین مطمئن
            video.classList.add("video-centered");
        });
    });
    </script>
    <?php
});
