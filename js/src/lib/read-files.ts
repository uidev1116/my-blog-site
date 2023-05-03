import { ExtendedFile } from '../types/media';

export default (files: FileList): Promise<ExtendedFile[]> => {
  const promiseArr = [];
  [].forEach.call(files, (f: File) => {
    const promise = new Promise((resolve) => {
      const objFileReader = new FileReader();
      if (f.type.match('image.*')) {
        objFileReader.onload = () => {
          resolve({
            file: f,
            filetype: 'image',
            preview: objFileReader.result,
          });
        };
        objFileReader.readAsDataURL(f);
      } else {
        objFileReader.onload = () => {
          resolve({
            file: f,
            filetype: 'file',
          });
        };
        objFileReader.readAsDataURL(f);
      }
      objFileReader.onerror = () => {
        resolve(null);
      };
    });
    promiseArr.push(promise);
  });
  return Promise.all(promiseArr);
};
