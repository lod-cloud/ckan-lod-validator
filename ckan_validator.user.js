// ==UserScript==
// @name           CKAN LOD Validator
// @description    CKAN LOD Validator
// @author         Anja Jentzsch <mail@anjajentzsch.de>
// @namespace      none
// @include        http://ckan.net/package/*
// ==/UserScript==

var allHTMLTags = new Array();

function insertAfter(parent, node, referenceNode) {
  parent.insertBefore(node, referenceNode.nextSibling);
}

function getElementByClass(type, theClass) {
	var allHTMLTags=document.getElementsByTagName(type);
	for (i=0; i<allHTMLTags.length; i++) {
		if (allHTMLTags[i].className == theClass) {
			return allHTMLTags[i];
		}
	}
}

firstHeading = getElementByClass('h2', 'head');
		
dbpediaLink = document.createElement("a");
dbpediaLink.setAttribute("style","font-size: 14px; background: url('http://www4.wiwiss.fu-berlin.de/lodcloud/ckan/validator/question.png') no-repeat scroll 0 0 transparent; padding-left: 20px;  padding-right: 20px");
dbpediaLink.setAttribute("href","http://www4.wiwiss.fu-berlin.de/lodcloud/ckan/validator/validate.php?package=" + location.pathname.replace(/\/package\//, ""));
dbpediaImage = document.createTextNode("Validate");
dbpediaLink.appendChild(dbpediaImage);
insertAfter(firstHeading, dbpediaLink, firstHeading.firstChild);
