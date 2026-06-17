export default function Logo({ size = 'sm', showText = true }) {
    const textSize = size === 'lg' ? 'text-xl' : size === 'md' ? 'text-lg' : 'text-base';

    return showText ? (
        <span className={`${textSize} font-bold text-primary tracking-[-0.02em]`}>InnerLink</span>
    ) : null;
}
