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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('inc/presentation');
include_spip('inc/documents');
include_spip('inc/date');

// Formulaire de description d'un document (titre, date etc)
// En mode Ajax pour eviter de recharger toute la page ou il se trouve
// (surtout si c'est un portfolio)

// http://doc.spip.org/@inc_legender_dist
function inc_legender_dist($id_document, $document, $script, $type, $id, $ancre, $deplier=false) {

	// + securite (avec le script exec=legender ca vient de dehors)
	if (!preg_match('/^\w+$/',$type, $r)) {
	  return;
	}

	// premier appel
	if ($document) {
		$flag = $deplier;
	} else
	// retour d'Ajax
	if ($id_document) {
		$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));
		$flag = 'ajax';
	}
	else
		return;

	$descriptif = $document['descriptif'];
	$titre = $document['titre'];
	$date = $document['date'];

	if ($document['mode'] == 'vignette') {
		$supp = 'image-24.gif';
		$label = _T('entree_titre_image');
		$taille = $vignette = '';
	  
	} else {
		$supp = 'doc-24.gif';
		$label = _T('entree_titre_document');
		$taille = formulaire_taille($document);
		$vignette = vignette_formulaire_legender($id_document, $document, $script, $type, $id, $ancre);
	}

	$entete = basename($document['fichier']);
	if (($n=strlen($entete)) > 20)
		$entete = substr($entete, 0, 10)."...".substr($entete, $n-10, $n);
	if (strlen($document['titre']))
		$entete = "<b>". lignes_longues(typo($titre),25) . "</b>";

	$contenu = '';
	if ($descriptif)
	  $contenu .=  lignes_longues(propre($descriptif),25)  . "<br />\n" ;
	if ($document['largeur'] OR $document['hauteur'])
	  $contenu .= _T('info_largeur_vignette',
		     array('largeur_vignette' => $document['largeur'],
			   'hauteur_vignette' => $document['hauteur']));
	else
	  $contenu .= taille_en_octets($document['taille']) . ' - ';

	if ($date) $contenu .= "<br />\n" . affdate($date);

	$corps =
	  (!$contenu ? '' :
	   "<br /><div class='verdana1' style='text-align: center;'>$contenu</div>") .
	  "<b>$label</b><br />\n" .

	  "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre).
	  "\" size='40'	onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /><br /><br />\n" .
	  date_formulaire_legender($date, $id_document) .
	  "<br />\n<b>".
	  _T('info_description_2').
	  "</b><br />\n" .
	  "<textarea name='descriptif_document' rows='4' class='formo' cols='*' onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">" .
	    entites_html($descriptif) .
	  "</textarea>\n" .
	  $taille;

	$att_bouton = " class='fondo spip_xx-small'";
	$att_span = " id='valider_doc$id_document' "
	. ($flag == 'ajax' ? '' : "class='display_au_chargement'")
	.  "style='text-align:"
	.  $GLOBALS['spip_lang_right']
	. ($flag == 'ajax' ? ';display:block' : "")
	. "'";

	if (!_DIR_RESTREINT)
		$corps = ajax_action_post("legender", $id_document, $script, "show_docs=$id_document&id_$type=$id#legender-$id_document", $corps, _T('bouton_enregistrer'), $att_bouton, $att_span, "&id_document=$id_document&id=$id&type=$type&ancre=$ancre")
		  . "<br class='nettoyeur' />";
	else {
		$corps = "<div>"
		       . $corps 
		       . "<span"
		       . $att
		       . "><input type='submit' class='fondo' value='"
		       . _T('bouton_enregistrer')
		       ."' /></span><br class='nettoyeur' /></div>";
		$redirect = parametre_url($script,'show_docs',$id_document,'&');
		$redirect = parametre_url($redirect,"id_$type",$id,'&');
		$redirect = parametre_url($redirect,"id_$type",$id,'&');
		$redirect = ancre_url($redirect,"legender-$id_document");
		$corps = generer_action_auteur("legender", $id_document, $redirect, $corps, "\nmethod='post'");
	}
	
	$corps .=  $vignette . "\n\n";

	$texte = _T('icone_supprimer_document');
	if (preg_match('/_edit$/', $script))
		$action = redirige_action_auteur('supprimer', "document-$id_document", $script, "id_$type=$id#$ancre");
	else {
		$s = ($ancre =='documents' ? '': '-');
		if (!_DIR_RESTREINT)
			$action = ajax_action_auteur('documenter', "$s$id/$type/$id_document", $script, "id_$type=$id&type=$type&s=$s#$ancre", array($texte));
		else{
			$redirect = str_replace('&amp;','&',$script);
			$action = generer_action_auteur('documenter', "$s$id/$type/$id_document", $redirect);
			$action = "<a href='$action'>$texte</a>";
		}
	}

	// le cas $id<0 correspond a un doc charge dans un article pas encore cree,
	// et ca buggue si on propose de supprimer => on ne propose pas
	if ($id > 0)
		$corps .= icone_horizontale($texte, $action, $supp, "supprimer.gif", false);

	$corps = "<div class='verdana1' style='color: "
	. $GLOBALS['couleur_foncee']
	. "; border: 1px solid "
	. $GLOBALS['couleur_foncee']
	. "; padding: 5px; margin: 3px; background-color: white;'>"
	. block_parfois_visible("legender-aff-$id_document", $entete, $corps, "text-align:center;", $flag)
	. "</div>";

	return ajax_action_greffe("legender-$id_document", $corps);
}


// http://doc.spip.org/@vignette_formulaire_legender
function vignette_formulaire_legender($id_document, $document, $script, $type, $id, $ancre)
{
	$id_vignette = $document['id_vignette'];
	$texte = _T('info_supprimer_vignette');

	if (preg_match('/_edit$/', $script)) {
		$iframe_redirect = generer_url_ecrire("documents_colonne","id=$id&type=$type",true);
		$action = redirige_action_auteur('supprimer', "document-$id_vignette", $script, "id_$type=$id&show_docs=$id_document#$ancre");
	} else {
		$iframe_redirect = generer_url_ecrire("documenter","id_$type=$id&type=$type",true);
		$s = ($ancre =='documents' ? '': '-');
		$action = ajax_action_auteur('documenter', "$s$id/$type/$id_vignette", $script, "id_$type=$id&type=$type&s=$s&show_docs=$id_document#$ancre", array($texte),'',"function(r,noeud) {noeud.innerHTML = r; \$('.form_upload',noeud).async_upload(async_upload_portfolio_documents);}");
	}

	$joindre = charger_fonction('joindre', 'inc');

	$supprimer = icone_horizontale($texte, $action, "vignette-24.png", "supprimer.gif", false);
	if ($id<0) $supprimer = ''; // cf. ci-dessus, article pas encore cree

	return "<hr style='margin-left: -5px; margin-right: -5px; height: 1px; border: 0px; color: #eeeeee; background-color: white;' />"
	. (!$id_vignette
	   ? $joindre($script, "id_$type=$id",$id, _T('info_vignette_personnalisee'), 'vignette', $type, $ancre, $id_document,$iframe_redirect)
	   : $supprimer);
}


// Bloc d'edition de la taille du doc (pour embed)
// http://doc.spip.org/@formulaire_taille
function formulaire_taille($document) {

	// (on ne le propose pas pour les images qu'on sait
	// lire, id_type<=3), sauf bug, ou document distant
	if ($document['id_type'] <= 3
	AND $document['hauteur']
	AND $document['largeur']
	AND $document['distant']!='oui')
		return '';
	$id_document = $document['id_document'];

	// Donnees sur le type de document
	$t = @spip_abstract_fetsel('inclus,extension',
		'spip_types_documents', "id_type=".$document['id_type']);
	$type_inclus = $t['inclus'];
	$extension = $t['extension'];

	# TODO -- pour le MP3 "l x h pixels" ne va pas
	if (($type_inclus == "embed" OR $type_inclus == "image")
	AND (
		// documents dont la taille est definie
		($document['largeur'] * $document['hauteur'])
		// ou distants
		OR $document['distant'] == 'oui'
		// ou tous les formats qui s'affichent en embed
		OR $type_inclus == "embed"
	)) {
		return "\n<br /><b>"._T('entree_dimensions')."</b><br />\n" .
		  "<input type='text' name='largeur_document' class='fondl spip_xx-small' value=\"".$document['largeur']."\" size='5' onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" />" .
		  " &#215; <input type='text' name='hauteur_document' class='fondl spip_xx-small' value=\"".$document['hauteur']."\" size='5' onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /> "._T('info_pixels');
	}
}

// http://doc.spip.org/@date_formulaire_legender
function date_formulaire_legender($date, $id_document) {
	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date, $regs)){
		$mois = $regs[2];
		$jour = $regs[3];
		$annee = $regs[1];
		$heure = $regs[4];
		$minute = $regs[5];
	}
	return  "<b>"._T('info_mise_en_ligne')."</b><br />\n" .
		afficher_jour($jour, "name='jour_doc' size='1' class='fondl spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_mois($mois, "name='mois_doc' size='1' class='fondl spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_annee($annee, "name='annee_doc' size='1' class='fondl spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") .
		afficher_heure($heure, "name='heure_doc' size='1' class='fondl spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") .
		afficher_minute($minute, "name='minute_doc' size='1' class='fondl spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") .
		"<br />\n";
}

?>
