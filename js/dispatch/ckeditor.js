ACMS.Dispatch.ckeditor=function(elm){var callback=function(){$(elm).each(function(){CKEDITOR.replace(this)})};var self=arguments.callee;if(typeof window["CKEDITOR"]!=="undefined"||self.loaded){callback(ACMS.Config.emoToolbar,ACMS.Config.emoConfig)}else if(self.stack){self.stack.push(callback)}else{self.stack=[callback];(new ACMS.SyncLoader).next(ACMS.Config.jsRoot+"library/ckeditor/ckeditor.js").load(function(){self.loaded=true;while(self.stack.length){self.stack.shift()()}})}};ACMS.Dispatch._ckeditorPre=function(){CKEDITOR_BASEPATH=ACMS.Config.jsRoot+"library/ckeditor/"};ACMS.Dispatch._ckeditorPost=function(){CKEDITOR.dtd.del=CKEDITOR.dtd.strike;CKEDITOR.dtd.ins=CKEDITOR.dtd.u;CKEDITOR.config.coreStyles_underline={element:"ins"};CKEDITOR.config.coreStyles_strike={element:"del"};CKEDITOR.disableAutoInline=ACMS.Config.ckeAutoInline;CKEDITOR.on("instanceReady",function(){for(var i in CKEDITOR.instances){CKEDITOR.instances[i].on("change",function(){CKEDITOR.instances[i].updateElement()})}})};