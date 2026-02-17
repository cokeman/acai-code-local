class QuillJS {
    constructor() {
        this.toolBarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            [ 'link', 'video', 'image'],
            [{ list: 'ordered'}, { 'list': 'bullet' }],
            [{ header: [1, 2, 3, 4, false] }],
            [{ align: [] }],            
            ['clean']
        ];
        
        this.initQuillOptions();
        
    }
    
    create(node){
        if (!node) {
            return;
        }
        
        if (!node.quill){
            window.setTimeout(()=>{
                node.quill = new Quill(node, {
                    theme: 'snow',
                    modules:{ 
                        'toolbar' : this.toolBarOptions
                    }
                });
                var toolbar = node.quill.getModule('toolbar');

                toolbar.addHandler('image', () => {
                    this.QuillImageHandler(node);
                });
                node.quill.on('text-change', (delta, oldDelta, source) => {
                    console.log("Wysiwyg modificado");
                    node.dispatchEvent(new Event("text-change"));
                    node.dispatchEvent(new Event("keyup"));
                });
            },10);
            
        }
        
    }

    QuillImageHandler(node){
        $("#modalUploader").remove();
        const modalUploader = `
        <div class="modal fade" id="modalUploader" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Subir foto</h4>
                    </div>
                    <div class="modal-body">
                        <div class="dropzone" id="dropzoneFile"><div class="dz-message">Arrastra aquí la foto</div></div>
                    </div>
                </div>
            </div>
        </div>
`;
        $("body").append(modalUploader);

        $("#modalUploader").modal();

        var mydropzone = $("#dropzoneFile").dropzone({ 
            url: "/lib/menus/modals/plupload/multiupload/upload.php",
            maxFilesize: 5, // MB
            maxFiles: 1,
            addRemoveLinks:false,
            success: function(file, serverResponse) {
                
                if (typeof serverResponse == "string") {
                    serverResponse = JSON.parse(serverResponse);
                }
                this.removeAllFiles();
                node.quill.focus();
                const range = node.quill.getSelection();
                
                node.quill.insertEmbed(range.index, 'image', serverResponse.absolutePath);
                $("#modalUploader").modal('hide');
            }
        });
    }
    initQuillOptions(){
        var BlockEmbed = Quill.import('blots/block/embed');
        
        class Video extends BlockEmbed {
            static create(value) {
                let node = super.create(value);
                node.classList.add("relative","p-1/5");
                let iframe = document.createElement('iframe');
                iframe.setAttribute('frameborder', '0');
                iframe.setAttribute('allowfullscreen', true);
                iframe.classList.add("absolute","top-0","left-0","w-full","h-full")
                iframe.setAttribute('src', value);
                node.appendChild(iframe);
                return node;
            }

            static value(domNode) {
                return domNode.firstChild.getAttribute('src');
            }
        }
        Video.blotName = 'video';
        Video.className = 'ql-video';
        Video.tagName = 'p';

        Quill.register({
            'formats/video': Video
        });
        
        class ImageBlot extends BlockEmbed {
            static create(value) {
                let wrapper = super.create(value);
                wrapper.classList.add("relative"/*,"p-1/6"*/);
                
                let node = document.createElement("img")
                node.setAttribute('alt', "");
                node.setAttribute('src', value);
                node.classList.add("w-full");
//                node.classList.add("absolute","top-0","left-0","w-full","h-full","object-contain","object-center");
                
                wrapper.appendChild(node);
                
                return wrapper;
            }

            static value(domNode) {
                return domNode.firstChild.getAttribute('src');
            }
        }
        ImageBlot.blotName = 'image';
        ImageBlot.className = 'ql-image';
        ImageBlot.tagName = 'p';

        Quill.register({
            'formats/image': ImageBlot
        });
        
        var Link = Quill.import('formats/link');

        class MyLink extends Link {
            static create(value) {
                let node = super.create(value);
                value = this.sanitize(value);
                node.setAttribute('href', value);
                node.classList.add("underline");
                node.removeAttribute('target');
                node.removeAttribute('style');
                return node;
            }
        }

        Quill.register(MyLink);
    }
    
}