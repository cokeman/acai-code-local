class Rest {
    static rootDomain = '';
    static xAcaiToken = '';
    static generalHeaders = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };

    static setDomain(domain){
        this.rootDomain = domain ? domain : '';
        console.log("New Root Domain Setted " + domain)
    }

    static setAcaiToken(token){
        this.generalHeaders['X-Acai-Token'] = token;
        console.log("X Acai token defined");
        return true;
    }

    static getCacheDataForGlobals(record){
        
        if (typeof this.flagGlobalCachedData == `undefined`) this.flagGlobalCachedData = false;
        if (typeof this.globals == `undefined`) this.globals = new Object();
        

        if (!record["tableName"]){
            if (record[0]){
                for (const cont in record){
                    for(const field in record[cont]){
                        this.getCacheDataForGlobals(record[cont][field]);    
                    }
                }
                return;
            }else{
                for(const field in record){
                    this.getCacheDataForGlobals(record[field]);    
                }
                return;
            }
        }
        
        if (record["tableName"] && record["recordNum"]){
            if (!this.globals[record["tableName"]]) this.globals[record["tableName"]] = [];
            if (!this.globals[record["tableName"]].includes(record["recordNum"])) this.globals[record["tableName"]].push(record["recordNum"]);
        }else if (record["newValues"]){
            for (const tableName in record["newValues"]){
                if (record["newValues"][tableName]["recordNum"]){
                    if (!this.globals[tableName]) this.globals[tableName] = [];
                    if (!this.globals[tableName].includes(record["newValues"][tableName]["recordNum"])) this.globals[tableName].push(record["newValues"][tableName]["recordNum"]);
                }
            }
        }
    }
    static async initGlobalCachedData(){
        if (typeof this.cachedData == `undefined`) this.cachedData = {};

        if (!this.flagGlobalCachedData){
            
            this.flagGlobalCachedData = true;
            
            for (const cont in myConfig){
                var myconf = myConfig[cont];
                if (myconf['config-vars']){
                    
                    var data = myconf['config-vars'];
                    for (const moduleName in data){
                        if (typeof data[moduleName] == "object") { this.getCacheDataForGlobals(data[moduleName]); }
                    }
                    
                }
            }
            
            const promises = [];
            
            if (this.globals){
                
                for (const tableName in this.globals){
                    const f = fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                        headers: this.generalHeaders,
                        method: "POST",
                        body: JSON.stringify({
                            method: 'GET',
                            where: 'num in(' + this.globals[tableName].join(",") + ')',
                            orderBy: 'num desc',
                            limit: 10000
                        })
                    })
                    .then(result => result.json())
                    .then(response => { 
                        if(response.error) console.error(response.error.adminMessage);
                        if (response.data){
                            var records = response.data;
                    
                            for (const cont in records){
                                if (!this.cachedData[btoa(tableName)]) this.cachedData[btoa(tableName)] = {};
                                if (!this.cachedData[btoa(tableName)][btoa('num=' + records[cont]["num"])]) this.cachedData[btoa(tableName)][btoa('num=' + records[cont]["num"])] = {};
                                this.cachedData[btoa(tableName)][btoa('num=' + records[cont]["num"])] = records[cont];
                            }
                            
                        }
                    });
                    promises.push(f);
                }
            }
            return Promise.all(promises);
            
            
        }
    }
    static query(query){
        if(this.cachedData[btoa(query)]){
            const cached = this.cachedData[btoa(query)];
            if (cached instanceof Promise) return cached;
            return new Promise(function(resolve, reject) {
                resolve({data:[cached]});
            });
        }
        
        this.cachedData[btoa(query)] = {};
        
        return this.cachedData[btoa(query)] = fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/dummy?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: this.generalHeaders,
                method: "POST",
                body: JSON.stringify({
                    method: 'GET',
                    query: query
                })
            })
            .then(result => result.json())
            .then(response => { 
                if(response.error) console.error(response.error.adminMessage);
                // console.log(response);
                return response;
            });
        
    }
    static deleteCache(tableName,where){
        if(this.cachedData && this.cachedData[btoa(tableName)] && this.cachedData[btoa(tableName)][btoa(where)]) return delete this.cachedData[btoa(tableName)][btoa(where)];
        return;
    }
    
    static get(tableName, where = '', orderBy = '', limit = 1000, cache = false,ignoreSchema = false) {
        
        if(this.cachedData && this.cachedData[btoa(tableName)] && this.cachedData[btoa(tableName)][btoa(where)]){
            const cached = this.cachedData[btoa(tableName)][btoa(where)];
            if (cached instanceof Promise) return cached;
            return new Promise(function(resolve, reject) {
                resolve({data:[cached]});
            });
        }
        if(this.cachedData && !this.cachedData[btoa(tableName)])
            this.cachedData[btoa(tableName)] = {};
        if(this.cachedData && this.cachedData[btoa(tableName)] && !this.cachedData[btoa(tableName)][btoa(where)])
            this.cachedData[btoa(tableName)][btoa(where)] = {};
        
        // return fetch(`${this.rootDomain}/rest/get/${tableName}?token=0d775395420d7f6a3f231a86a00e998c&where=${encodeURI(where)}&orderBy=${orderBy}&limit=${limit}`)
        
        return this.cachedData[btoa(tableName)][btoa(where)] = fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c${ignoreSchema ? "&ignoreSchema=1" : ""}`,{
                headers: this.generalHeaders,
                method: "POST",
                body: JSON.stringify({
                    method: 'GET',
                    where: where,
                    referer:window.location.host,
                    order: orderBy,
                    limit: limit
                })
            })
            .then(result => result.json())
            .then(response => { 
                if(response.error) console.error(response.error.adminMessage);
                // console.log(response);
                return response;
            });
            //.then(records => this.cachedData[btoa(tableName)][btoa(where)] = records);
    }
    static insert(tableName, records = [], options = {}) {
        return fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: this.generalHeaders,
                method: "POST",
                body: JSON.stringify({
                    method: 'POST',
                    records: records,
                    options: options
                })
            })
            .then(result => result.json())
            .then(response => {
                if(response.error) console.error(response.error.adminMessage);
                console.log(response);
                return response;
            });
    }
    static bulkUpdate(records_to_send) {
        for (let record in records_to_send) {
            
            let tableName = records_to_send[record].tableName;
            let where = records_to_send[record].where;

            if(this.cachedData[btoa(tableName)] && this.cachedData[btoa(tableName)][btoa(where)])
                delete this.cachedData[btoa(tableName)][btoa(where)];
            else if (this.cachedData[btoa(tableName)])
                delete this.cachedData[btoa(tableName)][btoa(where)];
        }
        
        return fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/bulkUpdate/?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: this.generalHeaders,
                method: "POST",
                body: JSON.stringify({
                    method: 'PATCH',
                    records: records_to_send,
                    options:{}
                })
            })
            .then(result => result.json())
            .then(response => {
                if(response.error) console.error(response.error.adminMessage);
                if (response.groupId) window.lastGroupId = response.groupId;
                //console.log(response);
                return response;
            });
    }
    static update(tableName, records = [], where = '',options = {}) {
        if(this.cachedData[btoa(tableName)] && this.cachedData[btoa(tableName)][btoa(where)])
            delete this.cachedData[btoa(tableName)][btoa(where)];
        else if (this.cachedData[btoa(tableName)])
            delete this.cachedData[btoa(tableName)][btoa(where)];
        
        return fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: this.generalHeaders,
                method: "POST",
                body: JSON.stringify({
                    method: 'PATCH',
                    records: records,
                    where: where,
                    options:options
                })
            })
            .then(result => result.json())
            .then(response => {
                if(response.error) console.error(response.error.adminMessage);
                //console.log(response);
                return response;
            });
    }
    static delete(tableName, where = '') {
        return fetch(`${this.rootDomain}/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: this.generalHeaders,
                method: "POST",
                body: JSON.stringify({
                    method: 'DELETE',
                    where: where
                })
            })
            .then(result => result.json())
            .then(response => {
                if(response.error) console.error(response.error.adminMessage);
                //console.log(response);
                return response;
            });
    }
    
}