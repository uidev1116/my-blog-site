ACMS.Dispatch._static2dynamic_yolp=function(elm){function parseQuery(query){var s=query.split("&"),data={},i=0,iz=s.length,param,key,value;for(;i<iz;i++){param=s[i].split("=");if(param[0]!==void 0){key=param[0];value=decodeURIComponent(param[1]!==void 0?param.slice(1).join("="):key);data[key]=data[key]?Array.prototype.concat(data[key],value):value}}return data}function setLayer(ly){var layerSet=ly;switch(ly){case"map":layerSet=YMap.setLayerSet(Y.LayerSetId.NORMAL);break;case"photo":layerSet=YMap.setLayerSet(Y.LayerSetId.PHOTO);break;case"map-b1":layerSet=YMap.setLayerSet(Y.LayerSetId.B1);break;case"railway":layerSet="MyRailwaySet";break;case"monotone":layerSet="MyMonotoneSet";break;case"bold":layerSet="MyBoldSet";break;case"midnight":layerSet="MyRMidnightSet";break;default:YMap.setLayerSet(Y.LayerSetId.NORMAL);break}YMap.setLayerSet(layerSet)}var query=parseQuery(elm.src.replace(/^[^?]*\?/,""));var msgs=elm.alt;var width=$(elm).width();var height=$(elm).height();var $div=$($.parseHTML('<div class="'+elm.className+'"></div>')).css({width:width+"px",height:height+"px",overflow:"hidden"});$(elm).replaceWith($div);var lat=query.lat,lng=query.lon,zoom=parseInt(query.z,10),layer=query.style.split(":")[1];YMap=new Y.Map($div.get(0),{configure:{doubleClickZoom:true,continuousZoom:true}});var railwayLayer=new Y.StyleMapLayer("railway");var monotoneLayer=new Y.StyleMapLayer("monotone");var boldLayer=new Y.StyleMapLayer("bold");var midnightLayer=new Y.StyleMapLayer("midnight");var railwaySet=new Y.LayerSet("路線図",[railwayLayer]);YMap.addLayerSet("MyRailwaySet",railwaySet);var monotoneSet=new Y.LayerSet("モノトーン",[monotoneLayer]);YMap.addLayerSet("MyMonotoneSet",monotoneSet);var boldSet=new Y.LayerSet("ボールド",[boldLayer]);YMap.addLayerSet("MyBoldSet",boldSet);var midnightSet=new Y.LayerSet("ミッドナイト",[midnightLayer]);YMap.addLayerSet("MyRMidnightSet",midnightSet);YMap.addControl(new Y.SliderZoomControlVertical);YMap.addControl(new Y.ScaleControl);if(ACMS.Config.yolpLayerSet=="on"){YMap.addControl(new Y.LayerSetControl)}YMap.drawMap(new Y.LatLng(lat,lng),zoom,Y.LayerSetId.NORMAL);var initLayer=query.mode;if(layer){initLayer=layer}setLayer(initLayer);var marker=new Y.Marker(new Y.LatLng(lat,lng));YMap.addFeature(marker);var content=msgs.replace(/\[\[:quot:\]\]/gim,'"').replace(/\[\[:lt:\]\]/gim,"<").replace(/\[\[:gt:\]\]/gim,">").replace(/\[\[:amp:\]\]/gim,"&")};