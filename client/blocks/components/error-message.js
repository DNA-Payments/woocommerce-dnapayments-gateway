const errorNoticeClass = 'wc-block-components-notice-banner is-error'

export const ErrorMessage = ({ messages = [] }) => {
    if (!messages?.length) return null

    return (
        <div className={errorNoticeClass}>
            {messages.length > 1 ? (
                <ul style={{ marginBottom: 0 }}>
                    {messages.map((message, i) => (
                        <li key={i}>{message}</li>
                    ))}
                </ul>
            ) : (
                messages[0]
            )}
        </div>
    )
}
