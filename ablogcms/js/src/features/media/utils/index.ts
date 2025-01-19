import { Transform } from '@dnd-kit/utilities';
import { FocalPoint, FocalPointCoordinates, MediaItem } from '../types';

export function getFocalPoint(media: MediaItem): FocalPoint {
  return media.media_focal_point
    ? (media.media_focal_point.split(',').map((str) => parseFloat(str)) as FocalPoint)
    : [50, 50];
}

export function coordinatesToTransform(
  focalPointCoordinates: FocalPointCoordinates,
  canvas: Cropper.CanvasData
): Transform {
  // focalPoint を使用して座標を計算
  const { x, y } = focalPointCoordinates;

  // transform の値として返す
  return {
    x: x - canvas.width / 2,
    y: y - canvas.height / 2,
    scaleX: 1,
    scaleY: 1,
  };
}

export function focalPointToCoordinates(
  focalPoint: [number, number],
  canvas: Cropper.CanvasData
): FocalPointCoordinates {
  const [x, y] = focalPoint;
  const percentX = x / 100;
  const percentY = y / 100;
  return {
    x: canvas.width * percentX,
    y: canvas.height * percentY,
  };
}
