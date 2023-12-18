import GeoPicker from 'geo-picker';
import Leaflet from 'leaflet';
import icon from 'leaflet/dist/images/marker-icon.png';
import icon2x from 'leaflet/dist/images/marker-icon-2x.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

export default (item) => {
  if (item.already === true) {
    return;
  }
  item.already = true;

  delete Leaflet.Icon.Default.prototype._getIconUrl;
  Leaflet.Icon.Default.mergeOptions({
    iconUrl: icon,
    iconRetinaUrl: icon2x,
    shadowUrl: iconShadow,
  });

  const geoPicker = new GeoPicker(item, {
    searchInput: '.js-osm-search',
    searchBtn: '.js-osm-search-btn',
    lngInput: '.js-osm-lng',
    latInput: '.js-osm-lat',
    zoomInput: '.js-osm-zoom',
    msgInput: '.js-osm-msg',
    map: '.js-open-street-map-picker',
  }, Leaflet);
  if (geoPicker.map) {
    geoPicker.run();
    ACMS.addListener('acmsAdminDelayedContents', () => {
      geoPicker.invalidateSize();
    });
  }
};
