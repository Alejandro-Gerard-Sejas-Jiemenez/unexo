export default function TablaResponsiva({ children, className = '' }) {
    return (
        <div className={`superficie overflow-hidden ${className}`}>
            <div className="overflow-x-auto">{children}</div>
        </div>
    );
}
