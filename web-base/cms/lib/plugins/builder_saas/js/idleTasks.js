var idleTasks = [
    //loadWebModules(),
    //loadLocalModules()
];
var idleTaskIndex = 0;

if ('requestIdleCallback' in window) {
    window.requestIdleCallback(function(){
    });
}