<?php
/*
Plugin Name: myguide create webp
Plugin URI:
Description: 画像をメディアにアップロードした時に、webpに生成する。変換したwebp画像は管理画面には反映されない。画像の圧縮も行う。※GDライブラリが必須
Author: MURAKAMI YU-KKI
Version: 0.1
Author URI:
*/


$myguide = new myguide_create_webp ;

class myguide_create_webp{
	function __construct(){
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		add_filter( "wp_generate_attachment_metadata" , array( $this , "myguide_img_meta" ),10 ,2 );
	}

	function myguide_img_meta($img_meta, $img_id){
		$img_path = get_attached_file( $img_id );
		$mime_type = get_post_mime_type( $img_id );

		//アップロードした画像webpではない場合に処理
		if( $mime_type != 'image/webp' ){
			// メインファイルの圧縮
			$this->myguide_compress_img( $img_path );

			//メインファイルを変換
			$res = $this->myguide_create_webp($img_path);

			//サムネイル画像の変換処理
			foreach ($img_meta['sizes'] as $key => $value) {
				//サムネイルのファイル名を取得
				$thumb_img_path = dirname($img_path).'/'.$value[ 'file' ];

					//サムネイルを変換
				$res = $this->myguide_create_webp($thumb_img_path );

			}
		}
		return $img_meta ;
	}


	// --------------------------------------
	// 元の画像読み込み
	//--------------------------------------
	private function myguide_load_img( $img_path ){
		$original_mime = wp_check_filetype(basename( $img_path ), null );
		if( $original_mime['type'] == "image/jpeg"){
			$img_original = imagecreatefromjpeg( $img_path );
		}elseif( $original_mime['type'] == "image/png" ) {
			$img_original = imagecreatefrompng( $img_path );
		}elseif( $original_mime['type'] == "image/gif" ) {
			$img_original = imagecreatefromgif( $img_path );
		} else {
			$img_original = false;
		}
		return $img_original;
	}

	// --------------------------------------
	// 画像を圧縮
	//--------------------------------------
	private function myguide_compress_img( $img_path ){

		// 画像情報の取得
		list($original_w, $original_h, $type) = getimagesize($img_path);

		//横幅が2560pxより大きかった場合は、処理しない（wpの機能で2560px以上は自動圧縮機能があるため：ver5.4から）
		if($original_w > 2560) return false;
		$w = $original_w;
		$h = $original_h;

		//画像の読み込み
		$img_original = $this->myguide_load_img( $img_path );

		// キャンバスを作成
		$canvas = imagecreatetruecolor($w, $h);
		//ブレンドモードを無効にする
		imagealphablending($canvas, false);
		//完全なアルファチャネル情報を保存するフラグをonにする
		imagesavealpha($canvas, true);

		imagecopyresampled($canvas, $img_original, 0,0,0,0, $w, $h, $original_w, $original_h);


		// 圧縮処理
		$original_mime = wp_check_filetype(basename( $img_path ), null );
		if( $original_mime['type'] == "image/jpeg"){
			imagejpeg($canvas, $img_path);
		}elseif( $original_mime['type'] == "image/png" ) {
			imagepng($canvas, $img_path, 9);
		}elseif( $original_mime['type'] == "image/gif" ) {
			imagegif($canvas, $img_path);
		}

		// 画像をメモリから開放します
		imagedestroy($img_original);
		imagedestroy($canvas);
	}

	// --------------------------------------
	// webp画像生成
	//--------------------------------------
	private function myguide_create_webp( $img_path ){
		//画像の読み込み
		$img_original = $this->myguide_load_img( $img_path );

		//ファイル名の拡張子を変える
		$extension = pathinfo($img_path);
		$img_path_webp = str_replace('.'.$extension['extension'], '.webp',$img_path);

		//webpを生成する
		imagewebp($img_original,$img_path_webp);

		// 画像をメモリから開放します
		imagedestroy($img_original);
	}
}
