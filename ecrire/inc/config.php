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
include_spip('inc/meta');

//
// Appliquer les valeurs par defaut pour les options non initialisees
//
// http://doc.spip.org/@init_config
function init_config() {
	// langue par defaut du site = langue d'installation (cookie spip_lang) sinon francais
	if (!$lang = $GLOBALS['spip_lang'])
		$lang = 'fr';

	$liste_meta = array(
		'nom_site' => _T('info_mon_site_spip'),
		'descriptif_site' => '',
		'activer_breves' => 'oui',
		'config_precise_groupes' => 'non',
		'mots_cles_forums' =>  'non',
		'articles_surtitre' => 'oui',
		'articles_soustitre' => 'oui',
		'articles_descriptif' => 'oui',
		'articles_chapeau' => 'oui',
		'articles_ps' => 'oui',
		'articles_redac' => 'non',
		'articles_mots' => 'oui',
		'post_dates' => 'oui',
		'articles_urlref' => 'non',
		'creer_preview' => 'non',
		'taille_preview' => 150,
		'articles_modif' => 'non',

		'activer_sites' => 'oui',
		'proposer_sites' => 0,
		'activer_syndic' => 'oui',
		'visiter_sites' => 'non',
		'moderation_sites' => 'non',

		'forums_publics' => 'posteriori',
		'accepter_inscriptions' => 'non',
		'accepter_visiteurs' => 'non',
		'prevenir_auteurs' => 'non',
		'suivi_edito' => 'non',
		'quoi_de_neuf' => 'non',
		'forum_prive_admin' => 'non',

		'activer_moteur' => 'oui',
		'articles_versions' => 'non',
		'articles_ortho' => 'non',
		'preview' => 'non',
		'activer_statistiques' => 'oui',

		'documents_article' => 'oui',
		'documents_rubrique' => 'non',
		'charset' => _DEFAULT_CHARSET,
		'syndication_integrale' => 'oui',

		'creer_htpasswd' => 'non',
		'creer_htaccess' => 'non',

		'langue_site' => $lang,

		'multi_articles' => 'non',
		'multi_rubriques' => 'non',
		'multi_secteurs' => 'non',
		'gerer_trad' => 'non',
		'langues_multilingue' => $GLOBALS['all_langs']
	);
	while (list($nom, $valeur) = each($liste_meta)) {
		if (!$GLOBALS['meta'][$nom]) {
			ecrire_meta($nom, $valeur);
			$modifs = true;
		}
	}

	if (isset($GLOBALS['meta']['nouvelle_install']) && $GLOBALS['meta']['nouvelle_install'] == 'oui') {
		effacer_meta('nouvelle_install');
		$modifs = true;
	}

	if ($modifs) ecrire_metas();

	calculer_langues_utilisees();
}


// http://doc.spip.org/@avertissement_config
function avertissement_config() {
	global $spip_lang_right, $spip_lang_left;
	$texte = debut_boite_info(true);

	$texte .= "\n<div class='verdana2' style='text-align: justify'>
	<p style='text-align: center'><b>"._T('avis_attention')."</b></p>";
	$texte .= http_img_pack("warning.gif", (_T('avis_attention')),
		"width='48' height='48' style='float: $spip_lang_right; padding-$spip_lang_left: 10px;'");
	$texte .= _T('texte_inc_config');
	$texte .= "</div>";
	$texte .= fin_boite_info(true);
	$texte .= "<p>&nbsp;</p>\n";
	
	return $texte;
}


// http://doc.spip.org/@bouton_radio
function bouton_radio($nom, $valeur, $titre, $actif = false, $onClick="") {
	static $id_label = 0;
	
	if (strlen($onClick) > 0) $onClick = " onclick=\"$onClick\"";
	$texte = "<input type='radio' name='$nom' value='$valeur' id='label_$id_label'$onClick";
	if ($actif) {
		$texte .= ' checked="checked"';
		$titre = '<b>'.$titre.'</b>';
	}
	$texte .= " /> <label for='label_$id_label'>$titre</label>\n";
	$id_label++;
	return $texte;
}


// http://doc.spip.org/@afficher_choix
function afficher_choix($nom, $valeur_actuelle, $valeurs, $sep = "<br />") {
	while (list($valeur, $titre) = each($valeurs)) {
		$choix[] = bouton_radio($nom, $valeur, $titre, $valeur == $valeur_actuelle);
	}
	return "\n".join($sep, $choix);
}


//
// Gestion des modifs
//

// http://doc.spip.org/@appliquer_modifs_config
function appliquer_modifs_config() {
	global $email_webmaster, $descriptif_site, $email_envoi, $post_dates, $tester_proxy, $test_proxy, $http_proxy, $activer_moteur;
	global $forums_publics, $forums_publics_appliquer;
	global $charset, $charset_custom, $langues_auth;
	global $retour_proxy;

	if (_request('adresse_site'))
		$_POST['adresse_site'] = preg_replace(",/?\s*$,", "", _request('adresse_site'));

	// Purger les squelettes si un changement de meta les affecte
	if ($post_dates AND ($post_dates != $GLOBALS['meta']["post_dates"]))
		$purger_skel = true;
	if ($forums_publics AND ($forums_publics != $GLOBALS['meta']["forums_publics"]))
		$purger_skel = true;

	// Appliquer les changements de moderation forum
	// forums_publics_appliquer : futur, saufnon, tous
	$accepter_forum = substr($forums_publics,0,3);
	if ($forums_publics_appliquer == 'saufnon')
	spip_query("UPDATE spip_articles SET accepter_forum='$accepter_forum'	WHERE accepter_forum != 'non'");
	else if ($forums_publics_appliquer == 'tous')
		spip_query("UPDATE spip_articles SET accepter_forum='$accepter_forum'");

	if ($accepter_forum == 'abo')
		ecrire_meta('accepter_visiteurs', 'oui');

	// Test du proxy : $tester_proxy est le bouton "submit"

	// http_proxy : ne pas prendre en compte la modif si le password est '****'
	if (preg_match(',:\*\*\*\*@,', $http_proxy))
		$http_proxy = $GLOBALS['meta']['http_proxy'];

	$retour_proxy = '';
	if ($tester_proxy) {
		if (!$test_proxy) {
			$retour_proxy = _T('info_adresse_non_indiquee');
		} else {
			include_spip('inc/distant');
			$page = recuperer_page($test_proxy, true);
			if ($page)
				$retour_proxy = "<p>"._T('info_proxy_ok')."</p>\n<tt>".couper(entites_html($page),300)."</tt>";
			else
				$retour_proxy = _T('info_impossible_lire_page', array('test_proxy' => $test_proxy))." <tt>".no_password_proxy_url($http_proxy)."</tt>.".aide('confhttpproxy');
		}
	}

	// Activer le moteur : dresser la liste des choses a indexer
	if ($activer_moteur == 'oui' AND ($activer_moteur != $GLOBALS['meta']["activer_moteur"])) {
		include_spip('inc/indexation');
		creer_liste_indexation();
	}

	if ($langues_auth) {
		set_request('langues_multilingue', join($langues_auth, ","));
	}

	if (isset($email_webmaster))
		ecrire_meta("email_webmaster", $email_webmaster);
	if (isset($email_envoi))
		ecrire_meta("email_envoi", $email_envoi);
	if ($charset == 'custom') $charset = $charset_custom;

	$liste_meta = array(
		'nom_site',
		'adresse_site',
		'descriptif_site',

		'activer_breves',
		'config_precise_groupes',
		'mots_cles_forums',
		'articles_surtitre',
		'articles_soustitre',
		'articles_descriptif',
		'articles_chapeau',
		'articles_ps',
		'articles_redac',
		'articles_mots',
		'post_dates',
		'articles_urlref',
		'creer_preview',
		'taille_preview',
		'articles_modif',

		'activer_sites',
		'proposer_sites',
		'activer_syndic',
		'visiter_sites',
		'moderation_sites',
		'http_proxy',

		'forums_publics',
		'accepter_inscriptions',
		'accepter_visiteurs',
		'prevenir_auteurs',
		'suivi_edito',
		'adresse_suivi',
		'adresse_suivi_inscription',
		'quoi_de_neuf',
		'adresse_neuf',
		'jours_neuf',
		'forum_prive_admin',

		'activer_moteur',
		'articles_versions',
		'articles_ortho',
		'preview',
		'activer_statistiques',

		'documents_article',
		'documents_rubrique',
		'syndication_integrale',

		'charset',
		'multi_articles',
		'multi_rubriques',
		'multi_secteurs',
		'gerer_trad',
		'langues_multilingue'
	);
	// Modification du reglage accepter_inscriptions => vider le cache
	// (pour repercuter la modif sur le panneau de login)
	if (isset($GLOBALS['accepter_inscriptions'])
	AND ($GLOBALS['accepter_inscriptions']
	!= $GLOBALS['meta']['accepter_inscriptions'])) {
		include_spip('inc/invalideur');
		suivre_invalideur("1"); # tout effacer
	}

	foreach($liste_meta as $i)
		if (!(_request($i)===NULL))
			ecrire_meta($i, _request($i));

	// langue_site : la globale est mangee par inc_version
	if ($lang = $GLOBALS['changer_langue_site']) {
		$lang2 = $GLOBALS['spip_lang'];
		if (changer_langue($lang)) {
			ecrire_meta('langue_site', $lang);
			changer_langue($lang2);
		}
	}

	ecrire_metas();

	// modifs de secu (necessitent une authentification ftp)
	$liste_meta = array(
			    'creer_htpasswd',
			    'creer_htaccess'
	);
	while (list(,$i) = each($liste_meta))
	  if (isset($GLOBALS[$i]) AND ($GLOBALS[$i] != $GLOBALS['meta'][$i]))
			$modif_secu=true;
	if ($modif_secu) {
		$admin = _T('info_modification_parametres_securite');
		include_spip('inc/admin');
		debut_admin($_POST['exec'], $admin);
		reset($liste_meta);
		while (list(,$i) = each($liste_meta))
			if (isset($GLOBALS[$i])) ecrire_meta($i, $GLOBALS[$i]);
		ecrire_metas();
		fin_admin($admin);
	}

	if ($purger_skel) {
		include_spip('inc/invalideur');
		purger_repertoire(_DIR_SKELS);
	}
}

// Ne pas afficher la partie 'password' du proxy
// http://doc.spip.org/@no_password_proxy_url
function no_password_proxy_url($http_proxy) {
	if ($p = @parse_url($http_proxy)
	AND $p['pass']) {
		$p['pass'] = '****';
		$http_proxy = glue_url($p);
	}
	return $http_proxy;
}


// Function glue_url : le pendant de parse_url 
// http://doc.spip.org/@glue_url
function glue_url ($url){
	if (!is_array($url)){
		return false;
	}
	// scheme
	$uri = (!empty($url['scheme'])) ? $url['scheme'].'://' : '';
	// user & pass
	if (!empty($url['user'])){
		$uri .= $url['user'].':'.$url['pass'].'@';
	}
	// host
	$uri .= $url['host'];
	// port
	$port = (!empty($url['port'])) ? ':'.$url['port'] : '';
	$uri .= $port;
	// path
	$uri .= $url['path'];
// fragment or query
	if (isset($url['fragment'])){
		$uri .= '#'.$url['fragment'];
	} elseif (isset($url['query'])){
		$uri .= '?'.$url['query'];
	}
	return $uri;
}

?>
