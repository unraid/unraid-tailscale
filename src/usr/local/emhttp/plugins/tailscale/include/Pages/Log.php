<?php
/* Copyright 2005-2023, Lime Technology
 * Copyright 2012-2023, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */

use EDACerton\PluginUtils\Translator;

if ( ! defined('Tailscale\PLUGIN_ROOT') || ! defined('Tailscale\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$tr = $tr ?? new Translator(Tailscale\PLUGIN_ROOT);

$log  = '/var/log/tailscale.log';
$prev = '/var/log/tailscale-utils.log';

$max    = 1000;
$select = [];
$logs   = [];

if (file_exists($prev)) {
    // add syslog-previous to front of logs array
    array_unshift($logs, $prev);
}
if (count($logs)) {
    // add syslog to front of logs array
    array_unshift($logs, $log);
    $select[] = "<select onchange='showLog(this.value)'>";
    foreach ($logs as $file) {
        $select[] = Tailscale\Utils::make_option(false, $file, basename($file));
    }
    $select[] = "</select>";
}
$select = implode($select);
?>
<script>
var logfile = "<?= $log;?>";

function resize() {
  $('pre.u').height(Math.max(window.innerHeight-350,330));
}

function showLog(log) {
  logfile = log;
  $('span.label input[type=checkbox]').prop('checked',true);
  $('span.label').each(function(){
    var type = $(this).attr('class').replace('label','').replace(/-/g,'');
    $(this).removeClass().addClass(type+' label');
  });
  timers.syslog = setTimeout(function(){$('div.spinner.fixed').show('slow');},500);
  $.post('/plugins/tailscale/include/get_log.php',{log:log,max:$('#max').val()||<?= $max;?>},function(data){
    clearTimeout(timers.syslog);
    $('pre.u').html(data);
    $('div.spinner.fixed').hide('slow');
  });
}
$(function() {
  $('input#max').on('keydown',function(e) {
    if (e.keyCode === 13) {
      e.preventDefault();
      e.stopImmediatePropagation();
      showLog(logfile);
    }
  });

  resize();
  $(window).bind('resize',function(){resize();});

  showLog(logfile);
});
</script>
<span><span class='lite label'>Log size:&nbsp;&nbsp;<input type='number' id='max' value='' placeholder='<?= $max;?>'></span><?= $select;?></span>
<input type="button" value="Refresh" onclick="showLog(logfile)">
<?php if (file_exists('/usr/local/emhttp/plugins/plugin-diagnostics/download.php')) { ?> <input type="button" value="<?= $tr->tr("settings.diagnostics"); ?>" onclick="window.open('/plugins/plugin-diagnostics/download.php?plugin=tailscale','_blank')"><?php } ?>
<pre class='u'></pre>