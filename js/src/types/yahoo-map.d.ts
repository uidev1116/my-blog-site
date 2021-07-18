declare namespace Y {
  namespace LayerSetId {
    var NORMAL: string
  }
  interface Control {}
  class SliderZoomControlVertical implements Control {

  }
  class ScaleControl implements Control {

  }
  class LayerSetControl implements Control {

  }
  class StyleMapLayer {
    constructor(str: string)
  }
  class LayerSet {
    constructor(str: string, layers: StyleMapLayer[])
  }
  class LatLng {
    constructor(lat: number, lng: number)
    lat(): number
    lng(): number
  }
  class LatLngBounds {
    constructor(sw: LatLng, ne: LatLng)
    containsLatLng(lng: LatLng): boolean
    getCenter(): LatLng
    getNorthEast(): LatLng
    getSouthWest(): LatLng
    extend(latlng: LatLng): void
  }
  class Point {
    x: number
    y: number
    constructor(x: number, y: number) 
  }
  class Projection {
    constructor()
    fromLatLngToPixel(latlng: LatLng, zoom: number): Point
    fromPixelToLatLng(pixel: Point, zoom: number): LatLng
  }
  class Map {
    constructor(id: string | HTMLElement)
    addLayerSet(layername: string, layerset: LayerSet): void
    addControl(control: Control)
    getBounds(): LatLngBounds
    getProjection(): Projection
    getZoom(): number
    drawMap(latlng: LatLng, zoom: number, layersetId: string)
    addFeature(feather: Feather): void
    removeFeature(feather: Feather): void
    getBoundsZoomLevel(bounds: LatLngBounds): number
    setZoom(zoom: number, animation: boolean, latlng: Y.LatLng, center: boolean): void
    bind(eventName: string, fn: Function): void
  }
  class Size {
    constructor(width: number, height: number)
  }
  type IconOption = {
    iconSize?: Size,
    iconAnchor?: Point,
    infoWindowAnchor?: Point,
    className?: string,
    iconHtml?: string
  }
  class Icon {
    constructor(src: string, option?: IconOption)
  }
  type MarkerOption = {
    icon: Icon
  }
  type LabelOption = {
    className: string
  }
  interface Feather {
    getLatLng(): LatLng
    getMap(): Map
    bind(eventName: string, fn: Function): void
  }
  class Marker implements Feather {
    constructor(latlng: LatLng, option?: MarkerOption)
    getLatLng(): LatLng
    getMap(): Map
    bind(eventName: string, fn: Function): void
    bindInfoWindow(html: string): void
  }
  class Label implements Feather {
    constructor(latlng: LatLng, label: string, option?: LabelOption)
    getLatLng(): LatLng
    getMap(): Map
    bind(eventName: string, fn: Function): void
  }
}