import { useLayoutEffect, useState } from 'react';

const useWindowSize = (): number[] => {
  const [size, setSize] = useState([window.innerWidth, window.innerHeight]);

  useLayoutEffect(() => {
    const handleSize = (): void => {
      setSize([window.innerWidth, window.innerHeight]);
    };

    window.addEventListener('resize', handleSize);

    return () => window.removeEventListener('resize', handleSize);
  }, []);
  return size;
};

export default useWindowSize;
