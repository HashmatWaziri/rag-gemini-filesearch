import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            viewBox="0 0 32 32"
            xmlns="http://www.w3.org/2000/svg"
            role="img"
            aria-label="GLC AI Platform"
            {...props}
        >
            <rect width="32" height="32" rx="7" fill="#059669" />
            <text
                x="50%"
                y="54%"
                dominantBaseline="middle"
                textAnchor="middle"
                fontSize="11"
                fontWeight="700"
                fontFamily="Inter, system-ui, sans-serif"
                fill="#ffffff"
            >
                GLC
            </text>
        </svg>
    );
}
