var installPluginsAcai = () => {};
var acaiLogin = () => {};

var script = document.createElement('script');

script.onload = () => {
    acaiLogin = (id) => {
        return new Promise((resolve,reject) => {
            var el = document.getElementById(id);

            if (!el) {
                console.log("Not element id defined");
                Swal.close();
                reject("Not element id defined");     
            }
            
            var form = el.querySelector("form");
            var loading = el.querySelector(".loading");
            
            if (form) form.classList.add("hidden");
            if (loading) loading.classList.remove("hidden");
            
            var data = {
                user:el.querySelector("[name = 'user']").value,
                password:el.querySelector("[name = 'password']").value,
                domain:el.querySelector("[name = 'domain']").value
            }

            var url = 'https://ws.cocosolution.com/api/auth';

            fetch(url,{
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'SimpleAuth ' + btoa(data.user + ":"  + data.password)
                    }
                })
                .then((respond) => {
                    return respond.json();
                })
                .then((json) => {
                    try{
                        if (json.success){
                            var domain = json.data.domains.find(rec => rec.domain == data.domain);
                            if (!domain){
                                throw("Not domain founded " + data.domain);   
                                
                            }
                            data.domain = domain.num;
                            data.password = sha1(data.password);

                            fetch(url,{
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Authorization': 'Login ' + btoa(data.user + ":"  + data.password + ":" + data.domain)
                                    }
                                })
                                .then((respond) => { return respond.json(); })
                                .then((json2) => {
                                    
                                    if (json2.success){
                                        resolve(json2);
                                    }else{
                                        throw("Login failed 2");   
                                    }
                                })
                            
                        }else{
                           throw("Login failed");   
                        }
                    }catch(exception){
                        Swal.close();
                        Swal.fire("Error",exception,"error");
                        reject(exception);     
                    }
                    
                    
                })
        })
        
        
    }



    installPluginsAcai = async (domain,token,plugins) => {    
        if (!token){
            Swal.fire({
                showConfirmButton:false,
                html:`
                    <div class="w-full lg:px-8" id="modalPluginsLogin">
                      
                      <img src="https://acaisuite.com/template/estandar/images/Logo_footer.svg" class="h-10 mt-4 mx-auto ">
                      <div class="relative text-left text-sm">
                        
                        <form action="">
                          <input type="hidden" name="domain" value="${domain}">
                          <div class="w-full px-4 py-2 mt-2 font-semibold text-gray-600">
                            <label class="block w-full"> User <input type="text" name="user" required="required" class="appearance-none block w-full bg-white shadow-none rounded px-4 py-2 border border-gray-300 mt-2">
                            </label>
                          </div>
                          <div class="w-full px-4 py-2 mt-2 font-semibold text-gray-600">
                            <label class="block w-full"> Contraseña <input type="password" name="password" autocomplete="current-password" required="required" class="appearance-none block w-full bg-white shadow-none rounded px-4 py-2 border border-gray-300 mt-2">
                            </label>
                          </div>
                          <div class="w-full px-4 py-2 mt-4 pb-8">
                            <button type="button" name="submitButton" onclick="acaiLogin('modalPluginsLogin').then((data) => { if (data.success) { Swal.close(); installPluginsAcai('${domain}',data.data.token,'${plugins}'); } })" class="bg-indigo-900 hover:bg-indigo-700 px-8 py-4 rounded text-white block w-full">Iniciar sesión</button>
                          </div>
                        </form>

                        <div class="loading flex flex-col items-center py-20 justify-center hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin w-10 h-10 icon icon-tabler icon-tabler-loader" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <line x1="12" y1="6" x2="12" y2="3" />
                              <line x1="16.25" y1="7.75" x2="18.4" y2="5.6" />
                              <line x1="18" y1="12" x2="21" y2="12" />
                              <line x1="16.25" y1="16.25" x2="18.4" y2="18.4" />
                              <line x1="12" y1="18" x2="12" y2="21" />
                              <line x1="7.75" y1="16.25" x2="5.6" y2="18.4" />
                              <line x1="6" y1="12" x2="3" y2="12" />
                              <line x1="7.75" y1="7.75" x2="5.6" y2="5.6" />
                            </svg>

                            <p class='text-center mt-2 text-center'>Accediendo a Acai</p>
                        </div>
                      </div>
                    </div>            
                `
            });   
        }else{

            var pluginsSep = plugins.split(",").filter(r => r);
            
            if (pluginsSep){
                Swal.fire({
                    showConfirmButton:false,
                    html:`
                        <div class="w-full lg:px-8" id="modalPluginsLogin">
                          
                          <img src="https://acaisuite.com/template/estandar/images/Logo_footer.svg" class="h-10 mt-4 mx-auto ">
                          <div class="relative text-left text-sm">


                            <div class="loading flex flex-col items-center py-20 justify-center ">

                                <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin w-10 h-10 icon icon-tabler icon-tabler-loader" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                  <line x1="12" y1="6" x2="12" y2="3" />
                                  <line x1="16.25" y1="7.75" x2="18.4" y2="5.6" />
                                  <line x1="18" y1="12" x2="21" y2="12" />
                                  <line x1="16.25" y1="16.25" x2="18.4" y2="18.4" />
                                  <line x1="12" y1="18" x2="12" y2="21" />
                                  <line x1="7.75" y1="16.25" x2="5.6" y2="18.4" />
                                  <line x1="6" y1="12" x2="3" y2="12" />
                                  <line x1="7.75" y1="7.75" x2="5.6" y2="5.6" />
                                </svg>

                                <p class='text-center mt-2 text-center'>Instalando plugins</p>
                            </div>
                          </div>
                        </div>            
                    `
                });  
                for (const plugin of pluginsSep){
                    await fetch('https://cms.cocosolution.com/admin.php?menu=admin&action=plugins&syncPlugin=1&pluginNum=payments',{
                        headers: {
                            'X-Acai-Token': token
                        }
                    }).then((respond) => {
                        return respond.json();
                    }).then((json) => {
                        if (json.success){
                            Swal.close();
                            Swal.fire("Perfecto","Los plugins se han instalado correctamente","success").then((value) => { window.location.reload(); });
                            
                        }else{
                            Swal.close();
                            Swal.fire("Error","Ha ocurrido un un error","warning");
                            
                        }
                        
                    })
                }
            }

        }

    }

};

script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';

document.head.appendChild(script);


/*
 * [js-sha1]{@link https://github.com/emn178/js-sha1}
 *
 * @version 0.6.0
 * @author Chen, Yi-Cyuan [emn178@gmail.com]
 * @copyright Chen, Yi-Cyuan 2014-2017
 * @license MIT
 */
!function(){"use strict";function t(t){t?(f[0]=f[16]=f[1]=f[2]=f[3]=f[4]=f[5]=f[6]=f[7]=f[8]=f[9]=f[10]=f[11]=f[12]=f[13]=f[14]=f[15]=0,this.blocks=f):this.blocks=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],this.h0=1732584193,this.h1=4023233417,this.h2=2562383102,this.h3=271733878,this.h4=3285377520,this.block=this.start=this.bytes=this.hBytes=0,this.finalized=this.hashed=!1,this.first=!0}var h="object"==typeof window?window:{},s=!h.JS_SHA1_NO_NODE_JS&&"object"==typeof process&&process.versions&&process.versions.node;s&&(h=global);var i=!h.JS_SHA1_NO_COMMON_JS&&"object"==typeof module&&module.exports,e="function"==typeof define&&define.amd,r="0123456789abcdef".split(""),o=[-2147483648,8388608,32768,128],n=[24,16,8,0],a=["hex","array","digest","arrayBuffer"],f=[],u=function(h){return function(s){return new t(!0).update(s)[h]()}},c=function(){var h=u("hex");s&&(h=p(h)),h.create=function(){return new t},h.update=function(t){return h.create().update(t)};for(var i=0;i<a.length;++i){var e=a[i];h[e]=u(e)}return h},p=function(t){var h=eval("require('crypto')"),s=eval("require('buffer').Buffer"),i=function(i){if("string"==typeof i)return h.createHash("sha1").update(i,"utf8").digest("hex");if(i.constructor===ArrayBuffer)i=new Uint8Array(i);else if(void 0===i.length)return t(i);return h.createHash("sha1").update(new s(i)).digest("hex")};return i};t.prototype.update=function(t){if(!this.finalized){var s="string"!=typeof t;s&&t.constructor===h.ArrayBuffer&&(t=new Uint8Array(t));for(var i,e,r=0,o=t.length||0,a=this.blocks;r<o;){if(this.hashed&&(this.hashed=!1,a[0]=this.block,a[16]=a[1]=a[2]=a[3]=a[4]=a[5]=a[6]=a[7]=a[8]=a[9]=a[10]=a[11]=a[12]=a[13]=a[14]=a[15]=0),s)for(e=this.start;r<o&&e<64;++r)a[e>>2]|=t[r]<<n[3&e++];else for(e=this.start;r<o&&e<64;++r)(i=t.charCodeAt(r))<128?a[e>>2]|=i<<n[3&e++]:i<2048?(a[e>>2]|=(192|i>>6)<<n[3&e++],a[e>>2]|=(128|63&i)<<n[3&e++]):i<55296||i>=57344?(a[e>>2]|=(224|i>>12)<<n[3&e++],a[e>>2]|=(128|i>>6&63)<<n[3&e++],a[e>>2]|=(128|63&i)<<n[3&e++]):(i=65536+((1023&i)<<10|1023&t.charCodeAt(++r)),a[e>>2]|=(240|i>>18)<<n[3&e++],a[e>>2]|=(128|i>>12&63)<<n[3&e++],a[e>>2]|=(128|i>>6&63)<<n[3&e++],a[e>>2]|=(128|63&i)<<n[3&e++]);this.lastByteIndex=e,this.bytes+=e-this.start,e>=64?(this.block=a[16],this.start=e-64,this.hash(),this.hashed=!0):this.start=e}return this.bytes>4294967295&&(this.hBytes+=this.bytes/4294967296<<0,this.bytes=this.bytes%4294967296),this}},t.prototype.finalize=function(){if(!this.finalized){this.finalized=!0;var t=this.blocks,h=this.lastByteIndex;t[16]=this.block,t[h>>2]|=o[3&h],this.block=t[16],h>=56&&(this.hashed||this.hash(),t[0]=this.block,t[16]=t[1]=t[2]=t[3]=t[4]=t[5]=t[6]=t[7]=t[8]=t[9]=t[10]=t[11]=t[12]=t[13]=t[14]=t[15]=0),t[14]=this.hBytes<<3|this.bytes>>>29,t[15]=this.bytes<<3,this.hash()}},t.prototype.hash=function(){var t,h,s=this.h0,i=this.h1,e=this.h2,r=this.h3,o=this.h4,n=this.blocks;for(t=16;t<80;++t)h=n[t-3]^n[t-8]^n[t-14]^n[t-16],n[t]=h<<1|h>>>31;for(t=0;t<20;t+=5)s=(h=(i=(h=(e=(h=(r=(h=(o=(h=s<<5|s>>>27)+(i&e|~i&r)+o+1518500249+n[t]<<0)<<5|o>>>27)+(s&(i=i<<30|i>>>2)|~s&e)+r+1518500249+n[t+1]<<0)<<5|r>>>27)+(o&(s=s<<30|s>>>2)|~o&i)+e+1518500249+n[t+2]<<0)<<5|e>>>27)+(r&(o=o<<30|o>>>2)|~r&s)+i+1518500249+n[t+3]<<0)<<5|i>>>27)+(e&(r=r<<30|r>>>2)|~e&o)+s+1518500249+n[t+4]<<0,e=e<<30|e>>>2;for(;t<40;t+=5)s=(h=(i=(h=(e=(h=(r=(h=(o=(h=s<<5|s>>>27)+(i^e^r)+o+1859775393+n[t]<<0)<<5|o>>>27)+(s^(i=i<<30|i>>>2)^e)+r+1859775393+n[t+1]<<0)<<5|r>>>27)+(o^(s=s<<30|s>>>2)^i)+e+1859775393+n[t+2]<<0)<<5|e>>>27)+(r^(o=o<<30|o>>>2)^s)+i+1859775393+n[t+3]<<0)<<5|i>>>27)+(e^(r=r<<30|r>>>2)^o)+s+1859775393+n[t+4]<<0,e=e<<30|e>>>2;for(;t<60;t+=5)s=(h=(i=(h=(e=(h=(r=(h=(o=(h=s<<5|s>>>27)+(i&e|i&r|e&r)+o-1894007588+n[t]<<0)<<5|o>>>27)+(s&(i=i<<30|i>>>2)|s&e|i&e)+r-1894007588+n[t+1]<<0)<<5|r>>>27)+(o&(s=s<<30|s>>>2)|o&i|s&i)+e-1894007588+n[t+2]<<0)<<5|e>>>27)+(r&(o=o<<30|o>>>2)|r&s|o&s)+i-1894007588+n[t+3]<<0)<<5|i>>>27)+(e&(r=r<<30|r>>>2)|e&o|r&o)+s-1894007588+n[t+4]<<0,e=e<<30|e>>>2;for(;t<80;t+=5)s=(h=(i=(h=(e=(h=(r=(h=(o=(h=s<<5|s>>>27)+(i^e^r)+o-899497514+n[t]<<0)<<5|o>>>27)+(s^(i=i<<30|i>>>2)^e)+r-899497514+n[t+1]<<0)<<5|r>>>27)+(o^(s=s<<30|s>>>2)^i)+e-899497514+n[t+2]<<0)<<5|e>>>27)+(r^(o=o<<30|o>>>2)^s)+i-899497514+n[t+3]<<0)<<5|i>>>27)+(e^(r=r<<30|r>>>2)^o)+s-899497514+n[t+4]<<0,e=e<<30|e>>>2;this.h0=this.h0+s<<0,this.h1=this.h1+i<<0,this.h2=this.h2+e<<0,this.h3=this.h3+r<<0,this.h4=this.h4+o<<0},t.prototype.hex=function(){this.finalize();var t=this.h0,h=this.h1,s=this.h2,i=this.h3,e=this.h4;return r[t>>28&15]+r[t>>24&15]+r[t>>20&15]+r[t>>16&15]+r[t>>12&15]+r[t>>8&15]+r[t>>4&15]+r[15&t]+r[h>>28&15]+r[h>>24&15]+r[h>>20&15]+r[h>>16&15]+r[h>>12&15]+r[h>>8&15]+r[h>>4&15]+r[15&h]+r[s>>28&15]+r[s>>24&15]+r[s>>20&15]+r[s>>16&15]+r[s>>12&15]+r[s>>8&15]+r[s>>4&15]+r[15&s]+r[i>>28&15]+r[i>>24&15]+r[i>>20&15]+r[i>>16&15]+r[i>>12&15]+r[i>>8&15]+r[i>>4&15]+r[15&i]+r[e>>28&15]+r[e>>24&15]+r[e>>20&15]+r[e>>16&15]+r[e>>12&15]+r[e>>8&15]+r[e>>4&15]+r[15&e]},t.prototype.toString=t.prototype.hex,t.prototype.digest=function(){this.finalize();var t=this.h0,h=this.h1,s=this.h2,i=this.h3,e=this.h4;return[t>>24&255,t>>16&255,t>>8&255,255&t,h>>24&255,h>>16&255,h>>8&255,255&h,s>>24&255,s>>16&255,s>>8&255,255&s,i>>24&255,i>>16&255,i>>8&255,255&i,e>>24&255,e>>16&255,e>>8&255,255&e]},t.prototype.array=t.prototype.digest,t.prototype.arrayBuffer=function(){this.finalize();var t=new ArrayBuffer(20),h=new DataView(t);return h.setUint32(0,this.h0),h.setUint32(4,this.h1),h.setUint32(8,this.h2),h.setUint32(12,this.h3),h.setUint32(16,this.h4),t};var y=c();i?module.exports=y:(h.sha1=y,e&&define(function(){return y}))}();