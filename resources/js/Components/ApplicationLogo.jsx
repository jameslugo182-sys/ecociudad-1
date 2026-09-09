export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            viewBox="0 0 64 64"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            aria-label="EcoCiudad"
        >
            <circle cx="32" cy="32" r="29" fill="#059669" />
            <path
                d="M18 39c0-10.5 6.7-19.7 17.7-23.7 4.9-1.8 9.6-1.6 12.3-.9-.4 8.7-3.5 16.5-10 20.2-5.1 2.9-10.3 2.2-14.2.3"
                fill="#D1FAE5"
            />
            <path
                d="M17 48c3.8-12.8 12.2-21.2 25.2-27.2M21 43h25M25 43V32M32 43V27M39 43V33"
                stroke="white"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
