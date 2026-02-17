var activeNode = null;
var moduleNodes = document.querySelectorAll(".moduleWrapperBuilder");
var timeout = null;
for (const indexNode in moduleNodes){
    let moduleNode = moduleNodes[indexNode];
    if (!moduleNode.tagName) continue;
    moduleNode.style.cursor = "pointer";
    moduleNode.addEventListener("click",(ev) => {
        ev.preventDefault();
        if(!moduleNode.scrollIntoView) return;
        moduleNode.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'center'
        });
        document.body.style.pointerEvents = "none";

        window.setTimeout(() => {
            document.body.style.pointerEvents = "auto";
        },600);

        activeNode = moduleNode;
        if (window.parent != window){
            window.parent.postMessage({key:"selectBuilderModule",indexNode:indexNode},'*');
        }

    })
    moduleNode.addEventListener("mouseenter",(ev) => {
        for (const moduleNode2 of moduleNodes){ moduleNode2.style.opacity = 0.5;}
        moduleNode.style.opacity = 1;
        clearTimeout(timeout);

    })
    moduleNode.addEventListener("mouseleave",(ev) => {
        clearTimeout(timeout);
        timeout = window.setTimeout((e) => {
            for (const moduleNode2 of moduleNodes){ moduleNode2.style.opacity = 1;}
        },1000)		
    })

}

document.body.addEventListener("click",(e) => {
    e.preventDefault();
})

window.onmessage = function(e){
    switch(e.data.key){
        case "scrollTo":
            if(!moduleNodes[e.data.indexNode] || !moduleNodes[e.data.indexNode].scrollIntoView) return;
            moduleNodes[e.data.indexNode].scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'center'
            });
            moduleNodes[e.data.indexNode].dispatchEvent(new Event("mouseenter"));
            moduleNodes[e.data.indexNode].dispatchEvent(new Event("mouseleave"));
        break;
    }
};