import { useEffect, useState } from 'react';

export function useScrollPosition(): number {
    const [scrollPosition, setScrollPosition] = useState(0);

    useEffect(() => {
        const onScroll = () => {
            setScrollPosition(window.scrollY);
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return scrollPosition;
}
