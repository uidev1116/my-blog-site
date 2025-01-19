export type ExtendedFile =
  | {
      file: File;
      filetype: 'image';
      preview: string;
    }
  | {
      file: File;
      filetype: 'file';
    };

export default function readFiles(files: File[] | FileList): Promise<ExtendedFile[]> {
  const promises: Promise<ExtendedFile>[] = [];
  Array.from(files).forEach((f) => {
    const promise = new Promise<ExtendedFile>((resolve, reject) => {
      const objFileReader = new FileReader();
      if (f.type.match('image.*')) {
        objFileReader.onload = () => {
          resolve({
            file: f,
            filetype: 'image',
            preview: objFileReader.result as string,
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
        reject(new Error(`Failed to read file: ${f.name}`));
      };
    });
    promises.push(promise);
  });
  return Promise.all(promises);
}
