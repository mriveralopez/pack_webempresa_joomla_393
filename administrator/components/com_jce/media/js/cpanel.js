/* jce - 2.6.36 | 2019-02-07 | https://www.joomlacontenteditor.net | Copyright (C) 2006 - 2019 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($,Wf){Wf.cpanel={options:{labels:{feed:"Feed",updates:"Updates",updates_available:"Updates Available"},feed:!0,updates:!0},init:function(options){$.extend(this.options,options||{});var o=this.options;o.feed&&($("ul.newsfeed").addClass("loading").html("<li>"+o.labels.feed+"</li>"),$.getJSON("index.php?option=com_jce&view=cpanel&task=feed",{},function(r){$("ul.newsfeed").removeClass("loading").empty(),$.each(r.feeds,function(k,n){$("ul.newsfeed").append('<li><a href="'+n.link+'" target="_blank" title="'+n.title+'">'+n.title+"</a></li>")})})),o.updates&&$.getJSON("index.php?option=com_jce&view=updates&task=update&step=check",{},function(r){if(r){if("string"==$.type(r)&&(r=$.parseJSON(r)),r.error){var $list=$(".ui-jce dl").append("<dt>"+o.labels.updates+'</dt><dd><span class="label label-important"><i class="icon-exclamation-sign icon-warning icon-white"></i>&nbsp;'+r.error+"</span></dd>");return!1}if(r.length){var $list=$(".ui-jce dl").append("<dt>"+o.labels.updates+'</dt><dd><a title="'+o.labels.updates+'" class="btn btn-small btn-info updates" href="#"><i class="icon-info-sign icon-info icon-white"></i>&nbsp;'+o.labels.updates_available+"</a></dd>");$("a.updates",$list).click(function(e){e.preventDefault(),$("#toolbar-updates button").click(),$("#toolbar-updates a.updates").each(function(){Wf.core.createDialog(this,{src:$(this).attr("href"),options:{width:780,height:560}})})})}}}),$("#newsfeed_enable").click(function(e){$("#toolbar-options button").click(),$("#toolbar-popup-options a.modal, #toolbar-config a.preferences").each(function(){Wf.core.createDialog(this,{src:$(this).attr("href"),options:{width:780,height:560}})}),e.preventDefault()})}},$(document).ready(function(){Wf.cpanel.init()})}(jQuery,Wf);