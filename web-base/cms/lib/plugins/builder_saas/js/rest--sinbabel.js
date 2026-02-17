class Rest {
    static cachedData = {};
    static flagGlobalCachedData = false;
    static globals = {};
    static getCacheDataForGlobals(record){
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
        if (!this.flagGlobalCachedData){
            
            this.flagGlobalCachedData = true;
            
            for (const cont in myConfig){
                var myconf = myConfig[cont];
                if (myconf['config-vars']){
                    
                    var data = myconf['config-vars'];
                    for (const moduleName in data){
                        this.getCacheDataForGlobals(data[moduleName]);
                    }
                    
                }
            }
            
            const promises = [];
            
            if (this.globals){
                
                for (const tableName in this.globals){
                    const f = fetch(`/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                        headers: {
                          'Accept': 'application/json',
                          'Content-Type': 'application/json'
                        },
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
    static get(tableName, where = '', orderBy = '', limit = 1000, cache = false,ignoreSchema = false) {
        
        if(this.cachedData[btoa(tableName)] && this.cachedData[btoa(tableName)][btoa(where)]){
            const cached = this.cachedData[btoa(tableName)][btoa(where)];
            if (cached instanceof Promise) return cached;
            return new Promise(function(resolve, reject) {
                resolve({data:[cached]});
            });
        }
        if(!this.cachedData[btoa(tableName)])
            this.cachedData[btoa(tableName)] = {};
        if(!this.cachedData[btoa(tableName)][btoa(where)])
            this.cachedData[btoa(tableName)][btoa(where)] = {};
        
        // return fetch(`/rest/get/${tableName}?token=0d775395420d7f6a3f231a86a00e998c&where=${encodeURI(where)}&orderBy=${orderBy}&limit=${limit}`)
        
        return this.cachedData[btoa(tableName)][btoa(where)] = fetch(`/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c${ignoreSchema ? "&ignoreSchema=1" : ""}`,{
                headers: {
                  'Accept': 'application/json',
                  'Content-Type': 'application/json'
                },
                method: "POST",
                body: JSON.stringify({
                    method: 'GET',
                    where: where,
                    orderBy: orderBy,
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
        return fetch(`/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: {
                  'Accept': 'application/json',
                  'Content-Type': 'application/json'
                },
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
    static update(tableName, records = [], where = '') {
        if(this.cachedData[btoa(tableName)] && this.cachedData[btoa(tableName)][btoa(where)])
            delete this.cachedData[btoa(tableName)][btoa(where)];
        else if (this.cachedData[btoa(tableName)])
            delete this.cachedData[btoa(tableName)][btoa(where)];
        
        return fetch(`/lib/plugins/builder_saas/api/v1/${tableName}?token=0d775395420d7f6a3f231a86a00e998c`,{
                headers: {
                  'Accept': 'application/json',
                  'Content-Type': 'application/json'
                },
                method: "POST",
                body: JSON.stringify({
                    method: 'PATCH',
                    records: records,
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