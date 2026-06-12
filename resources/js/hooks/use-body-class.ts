import { useEffect } from 'react';

export function useBodyClass(className: string): void {
    useEffect(() => {
        const classList = className.split(/\s+/).filter(Boolean);

        classList.forEach((name) => {
            document.body.classList.add(name);
        });

        return () => {
            classList.forEach((name) => {
                document.body.classList.remove(name);
            });
        };
    }, [className]);
}
