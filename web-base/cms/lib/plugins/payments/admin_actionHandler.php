<?
if ($menu!="cms_payments") return;

if (!@$external) showHeader(true);

$data = file_get_contents("https://".$CURRENT_USER["domain"]["domain"]."/cms/lib/plugins/cms_api/v3/schemaBase.json");
global $json;
$json = [];
try{
    $json = json_decode($data,true);
}catch(Exception $e){
    ?>
    <style>#page-container.header-fixed-top{padding:0px;}</style>
    <div id="page-content"> 
        <h3 class="font-bolg text-2xl"> Ha ocurrido un error al recuperar los datos</h3>
    </div>
    <?
    die();
}

?>
<style>#page-container.header-fixed-top{padding:0px;}</style>
<div id="page-content">
    <?
    $limit = '0, 100';
    if(@$_REQUEST['limit']) {
        $limit = @$_REQUEST['limit'];
    }
    $shop = CocoDB::get('aux_plg_payments', '', 'num DESC', $limit, ['prefix' => '', 'ignoreSchema' => true]);
    ?>
    <?php if (count($shop) > 0): ?>
        <form action="" class="flex">
            <input type="hidden" name="menu" value="cms_payments">
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" name="limit" value="<?=$limit?>">
            <button class="ml-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Enviar limit</button>
        </form>
        <div class="flex flex-col mt-8">
          <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
              <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo implode('</th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">', array_keys(current($shop))); ?></th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($shop as $row): $row = array_map('htmlentities', $row); ?>
                    <tr class=" hover:bg-gray-200">
                    <?php foreach ($row as $key => $value): ?>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <?php if (in_array($key, ['records', 'request', 'ipn_response'])): ?>
                            <textarea>
                        <?php endif ?>
                        <?php echo $value; ?>
                        <?php if (in_array($key, ['records', 'request', 'ipn_response'])): ?>
                            </textarea>
                            <button class="payments_console_log ml-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Console log</button>
                        <?php endif ?>
                      </td>
                    <?php endforeach ?>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
    <?php endif; ?>
    <script type="text/javascript">
        document.querySelectorAll('.payments_console_log').forEach(each => {
            each.addEventListener('click', payments_console_log);
        })
        function payments_console_log(e) {
            console.log(JSON.parse(e.target.parentNode.querySelector('textarea').value));
        }
    </script>
</div>
<? showFooter(true);?>
<? if (!@$external) die(); ?>
