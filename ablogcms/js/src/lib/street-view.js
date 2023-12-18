export default (item) => {
  ACMS.Library.googleLoadProxy('maps', '3', {
    callback: () => {
      const lat = parseFloat(item.getAttribute('data-lat'));
      const lng = parseFloat(item.getAttribute('data-lng'));
      let pitch = parseFloat(item.getAttribute('data-pitch'));
      let heading = parseFloat(item.getAttribute('data-heading'));
      let zoom = parseFloat(item.getAttribute('data-zoom'));
      pitch = Number.isNaN(pitch) ? 0 : pitch;
      heading = Number.isNaN(heading) ? 0 : heading;
      zoom = Number.isNaN(zoom) ? 0 : zoom;

      // eslint-disable-next-line no-new
      new google.maps.StreetViewPanorama(item, {
        position: { lat, lng },
        pov: { heading, pitch, zoom },
      });
    },
    options: {
      region: ACMS.Config.s2dRegion,
    },
  });
};
