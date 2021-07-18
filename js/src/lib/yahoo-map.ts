import yahooMapLoader from 'yahoo-map-loader';
import Cluster from 'yahoo-map-cluster';

export default (element: HTMLElement) => {
  const lat = parseFloat(element.dataset.lat);
  const lng = parseFloat(element.dataset.lng);
  const zoom = parseFloat(element.dataset.zoom);
  const cluster = element.dataset.cluster;
  const markerStr = element.dataset.markers || '';
  const messageStr = element.dataset.messages || '';
  const markers = markerStr.split('|').map((item) => {
    const latlng = item.split(',');
    return latlng.map((str) => parseFloat(str));
  })
  const messages = messageStr.split('[[:split:]]');

  yahooMapLoader.exportGlobal = true;
  yahooMapLoader.appId = ACMS.Config.yahooApiKey;
  yahooMapLoader.load((Y) => {
    
    const map = new Y.Map(element);
    //------------
    // layer set
    const railwayLayer = new Y.StyleMapLayer('railway');
    const monotoneLayer = new Y.StyleMapLayer('monotone');
    const boldLayer = new Y.StyleMapLayer('bold');
    const midnightLayer = new Y.StyleMapLayer('midnight');

    const railwaySet = new Y.LayerSet('路線図', [railwayLayer]);
    map.addLayerSet('MyRailwaySet', railwaySet);

    const monotoneSet = new Y.LayerSet('モノトーン', [monotoneLayer]);
    map.addLayerSet('MyMonotoneSet', monotoneSet);

    const boldSet = new Y.LayerSet('ボールド', [boldLayer]);
    map.addLayerSet('MyBoldSet', boldSet);

    const midnightSet = new Y.LayerSet('ミッドナイト', [midnightLayer]);
    map.addLayerSet('MyRMidnightSet', midnightSet);

    //------------
    // controller
    map.addControl(new Y.SliderZoomControlVertical());
    map.addControl(new Y.ScaleControl());
    if (ACMS.Config.yolpLayerSet == 'on') {
      map.addControl(new Y.LayerSetControl());
    }
    map.drawMap(new Y.LatLng(lat, lng), zoom, Y.LayerSetId.NORMAL);
    const ymarkers = markers.map((marker, i) => {
      const ymarker = new Y.Marker(new Y.LatLng(marker[0], marker[1]));
      if (messages[i]) {
        ymarker.bindInfoWindow(messages[i]);
      }
      return ymarker;
    });
    if (cluster) {
      new Cluster(map, ymarkers);
    } else {
      ymarkers.forEach((marker) => {
        map.addFeature(marker);
      });
    }
  });
};
