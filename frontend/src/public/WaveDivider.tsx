type WaveDividerProps = {
  fill: string
  flip?: boolean
}

/** Curved section divider — from garden-store Home.tsx */
export function WaveDivider({ fill, flip = false }: WaveDividerProps) {
  return (
    <div className="gs-wave-divider" aria-hidden="true">
      <svg viewBox="0 0 1440 50" preserveAspectRatio="none" style={{ display: 'block', transform: flip ? 'scaleY(-1)' : undefined }}>
        <path d="M0,0 C360,50 1080,0 1440,30 L1440,50 L0,50 Z" fill={fill} />
      </svg>
    </div>
  )
}
