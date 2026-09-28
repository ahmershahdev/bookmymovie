export default function SocialLogin({ providers }: { providers: { key: string; label: string; url: string }[] }) {
    if (!providers.length) return null;

    return (
        <>
            <div className={`grid gap-2 ${providers.length > 1 ? 'sm:grid-cols-2' : ''}`}>
                {/* Plain links: OAuth leaves the app, so this must be a full page navigation. */}
                {providers.map((provider) => <a key={provider.key} href={provider.url} className="btn btn-ghost w-full">Continue with {provider.label}</a>)}
            </div>
            <div className="label my-8 flex items-center gap-4"><span className="h-px flex-1 bg-line" />or with email<span className="h-px flex-1 bg-line" /></div>
        </>
    );
}
