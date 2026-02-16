<?
if ($var["field"]["type"] != $options["fieldType"]["type"]) return;

$description = @$var['field']['description'];
$encodedValue = htmlspecialchars(@$var['record'][$var['field']['name']]);
if ($encodedValue=="") $encodedValue="";
print <<<__HTML__
      <div class="form-group form-group-color">
        <label for="title" class="col-md-3">{$var['field']['label']}</label>
        <div class="col-md-9">
          <input type="password" class="form-control" autocomplete="new-password" name="{$var['field']['name']}" data-type="{$var["field"]["type"]}" value="$encodedValue"/>
          $description
        </div>
      </div>
__HTML__;

