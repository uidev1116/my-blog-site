import { FocusedImage } from 'image-focus'

export default (target) => {
  target.style.visibility = 'visible'
  new FocusedImage(target)
}
