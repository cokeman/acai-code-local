<?


/*

Aquí dentro podemos meter clases básicas o autogenerar todas las clases básicas que debe tener cocotailwind siempre.

Añadir en el array de clases las clases básicas y modificar en el servidor de webs en /var/www/vhost/cocotail-classes..

*/

$clases = [
    // Listado de clases que utiliza por defecto el WYSIWYG
    'mx-auto','w-full','table',
    'table-auto', 'my-3','w-full',
    'hover:bg-gray-200',
    'border', 'bg-gray-100', 'p-1',
    'border', 'p-1','border-gray-200',
    'm-1',
    'float-right', 'max-w-screen-sm',
    'w-full', 'max-w-full', 'flex', 'flex-wrap', 'justify-center', 'mx-auto',
    'text-center',
    'border', 'my-3',
    'relative', 'ml-5',
    'list-disc',
    'list-decimal',
    'aboslute left-0 -ml-5',
    'text-3xl',
    'text-2xl',
    'text-xl',
    'text-sm',
    'absolute','left-0','-ml-6','bg-white',
    // Otras clases

];

$arr1 = ['', 'hover:'];
$arr2 = ['text', 'bg','border','from','to'];
$arr3 = ['transparent','current','black','white'];

foreach ($arr1 as $val1) {
    foreach ($arr2 as $val2) {
        foreach ($arr3 as $val3) {
            $clases[] = $val1.$val2.'-'.$val3;
        }
    }
}

$arr1 = ['', 'hover:'];
$arr2 = ['text','bg','border','from','to'];
$arr3 = ['gray','red','yellow','green','blue','indigo','purple','ping'];
$arr4 = [50,100,200,300,400,500,600,700,800,900];

foreach ($arr1 as $val1) {
    foreach ($arr2 as $val2) {
        foreach ($arr3 as $val3) {
            foreach ($arr4 as $val4) {
                $clases[] = $val1.$val2.'-'.$val3.'-'.$val4;
            }
        }
    }
}
?>
<textarea style="width: 95vw; height: 95vh;">
<? echo "Clases: " . count($clases) . "\n"; ?>
<div class="<?=join($clases, ' ')?>"></div>
<ul><li></li></ul>
<h1></h1>
<h2></h2>
<h3></h3>
<h4></h4>
<h5></h5>
<h6></h6>
</textarea>
