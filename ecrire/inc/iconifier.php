<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');

// http://doc.spip.org/@inc_iconifier_dist
function inc_iconifier_dist($id_objet, $id,  $script, $flag_modif=true) {

	$texteon = $GLOBALS['logo_libelles'][($id OR $id_objet != 'id_rubrique') ? $id_objet : 'id_racine'];

	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	
	// Add the redirect url when uploading via iframe
	$iframe_script = generer_url_ecrire('iconifier',"type=$id_objet&$id_objet=$id&script=$script",true);
	$iframe = "<input type='hidden' name='iframe_redirect' value='".rawurlencode($iframe_script)."' />\n";

	if (!$logo = $chercher_logo($id, $id_objet, 'on')) {
		if ($flag_modif) {
			$masque = indiquer_logo($texteon, $id_objet, 'on', $id, $script, $iframe);
			$res = block_parfois_visible('on', "<b>$texteon</b>", $masque);
		}
	} else {
		list($img, $clic) = decrire_logo($id_objet,'on',$id, 170, 170, $logo, $texteon, $script, $flag_modif);

		$masque = block_parfois_visible('on', "<b>$texteon</b><br />$img", $clic, 'margin-bottom: -2px');

		$res = "<div style='text-align: center'>$masque</div><br /><br />";;
		$texteoff = _T('logo_survol');

		if ($logo = $chercher_logo($id, $id_objet, 'off')) {

			list($img, $clic) = decrire_logo($id_objet, 'off', $id, 170, 170, $logo, $texteoff, $script, $flag_modif);

			$masque = block_parfois_visible('off', "<b>$texteoff</b><br />$img", $clic, 'margin-bottom: -2px');

			$res .= "<div style='text-align: center'>$masque</div>";
		} else {
			if ($flag_modif) {
				$masque = indiquer_logo($texteoff, $id_objet, 'off', $id, $script, $iframe);
				$res .= block_parfois_visible('off', "<b>$texteoff</b>", $masque);
			}
		}
	}

	if ($res) {
		$res = debut_cadre_relief("image-24.gif", true)
		. "<div class='verdana1' style='text-align: center;'>"
		. $res
		. "</div>"
		. fin_cadre_relief(true);

		$js = "";
		if(_request("exec")!="iconifier") {
			$js .= "<script src='"._DIR_JAVASCRIPT."async_upload.js' type='text/javascript'></script>\n";
			$js .= <<<EOF
      <script type='text/javascript'>
      $("form.form_upload_icon").async_upload(async_upload_icon);
      </script>
EOF;
		}
		return ajax_action_greffe("iconifier-$id", $res).$js;
	}
	else return '';

}

global $logo_libelles;
$logo_libelles = array(
		       'id_article' => _T('logo_article').aide ("logoart"),
		       'id_auteur'  => _T('logo_auteur').aide ("logoart"),
		       'id_breve'   => _T('logo_breve').aide ("breveslogo"),
		       'id_syndic'  => _T('logo_site')." ".aide ("rublogo"),
		       'id_mot'     => _T('logo_mot_cle').aide("breveslogo"),
		       'id_rubrique' => _T('logo_rubrique')." ".aide ("rublogo"),
		       'id_racine' => _T('logo_standard_rubrique')." ".aide ("rublogo")
		       );

// http://doc.spip.org/@indiquer_logo
function indiquer_logo($titre, $id_objet, $mode, $id, $script, $iframe_script) {

	global $formats_logos;
	$afficher = "";
	$reg = '[.](' . join('|', $formats_logos) . ')$';

	if ($GLOBALS['flag_upload']
	AND $dir_ftp = determine_upload()
	AND $fichiers = preg_files($dir_ftp, $reg)) {
		foreach ($fichiers as $f) {
			$f = substr($f, strlen($dir_ftp));
			$afficher .= "\n<option value='$f'>$f</option>";
		}
	}
	if (!$afficher) {
		if ($dir_ftp) {
			$afficher = _T('info_installer_images_dossier',
				array('upload' => '<b>' . joli_repertoire($dir_ftp) . '</b>'));
		}
	} else {
		$afficher = "\n<div style='text-align: left'>" .
			_T('info_selectionner_fichier',
				array('upload' => '<b>' . joli_repertoire($dir_ftp) . '</b>')) .
			":</div>" .
			"\n<select name='source' class='forml' size='1'>$afficher\n</select>" .
			"\n<div align='" .
			$GLOBALS['spip_lang_right'] .
			"'><input name='sousaction2' type='submit' value='".
			_T('bouton_choisir') .
			"' class='fondo spip_xx-small'  /></div>";
	}

	$afficher = "\n" .
		_T('info_telecharger_nouveau_logo') .
		"<br />" .
		"\n<input name='image' type='file' class='forml spip_xx-small' size='15' />" .
		"<div align='" .  $GLOBALS['spip_lang_right'] . "'>" .
		"\n<input name='sousaction1' type='submit' value='" .
		_T('bouton_telecharger') .
		"' class='fondo spip_xx-small' /></div>" .
		$afficher;

	$type = type_du_logo($id_objet);
	return generer_action_auteur('iconifier',
		"$id+$type$mode$id",
		generer_url_ecrire($script, "$id_objet=$id", true), 
		$iframe_script.$afficher,
		" method='post' enctype='multipart/form-data' class='form_upload_icon'");
}

// http://doc.spip.org/@decrire_logo
function decrire_logo($id_objet, $mode, $id, $width, $height, $img, $titre="", $script="", $flag_modif=true) {

	list($fid, $dir, $nom, $format) = $img;
	include_spip('inc/filtres_images');
	$res = image_reduire("<img src='$fid' alt='' />", $width, $height);

	if ($res)
	    $res = "<div><a href='" .	$fid . "'>$res</a></div>";
	else
	    $res = "<img src='$fid' width='$width' height='$height' alt=\"" . htmlentities($titre) . '" />';
	if ($taille = @getimagesize($fid))
		$taille = _T('info_largeur_vignette', array('largeur_vignette' => $taille[0], 'hauteur_vignette' => $taille[1]));

	return array($res,
		"<div class='spip_xx-small'>" . $taille
		. ($flag_modif
			? "\n<br />["
				. ajax_action_auteur("iconifier", "$id-$nom.$format",
				$script, "$id_objet=$id&type=$id_objet",
				array(_T('lien_supprimer')),
				'',"function(r,status) {this.innerHTML = r; \$('.form_upload_icon',this).async_upload(async_upload_icon);}") ."]"
			: '')
			. "</div>");
}
?>
